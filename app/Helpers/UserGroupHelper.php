<?php

namespace App\Helpers;

use App\UserGroup;
use App\Helpers\CognitoHelper;
use App\Helpers\ShopifyHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserGroupHelper
{

    /**
     * Get Affiliates (UserGroups with Location or Commission assigned) and then cache the $users assigned to each UserGroup
     * @param Request $request
     * @return array
     */
    public static function getAllWithCommissionFromRequest(Request $request)
    {
        $userGroups = UserGroup::with(['commission', 'location'])
            ->get()
            ->where('commission', '!==', null)
            ->values()
            ->all();

        if((bool)$request->input('include_users') === TRUE) {
            // the CognitoHelper IS using caching, but it seems as though the cache is refreshed very frequently
            // probably because the pagination token changes on Cognito's side very frquently
            // to get around this, cache the end results directly
            if(Cache::has('allAffiliateUsersGroupController')) {
                $allUsers = collect(json_decode(
                    Cache::get('allAffiliateUsersGroupController'),
                    TRUE
                ));
            } else {
                $allUsers = (new CognitoHelper(1440))
                    ->listUsers();

                Cache::put(
                    'allAffiliateUsersGroupController',
                    json_encode($allUsers),
                    1440
                );
            }

            $shopifyUserIDs = collect($allUsers)
                ->filter(function($user) {
                    return !empty($user['shopify_id']);
                })
                ->pluck('shopify_id')
                ->all();
            $shopifyUsers = (new ShopifyHelper(1440))->getCustomers($shopifyUserIDs);

            foreach($userGroups as $userGroup) {
                $userGroup->loadUsers(
                    $allUsers,
                    $shopifyUsers
                );
            }
        }

        return $userGroups;
    }

}
