<?php

namespace App\Helpers;

use App\UserGroup;
use App\Helpers\CognitoHelper;
use App\Helpers\ShopifyHelper;
use Illuminate\Support\Facades\Cache;

class UserGroupHelper
{

    /**
     * Get Affiliates (UserGroups with Location or Commission assigned) and then cache the $users assigned to each UserGroup
     * @param bool $includeUsers
     * @param null|string $includedUsersEnabledStatus (Get Cognito users by a specific enabled status. 'enabled' (default), 'disabled', 'any'
     * @param bool $includeLocationAddresses
     * @return array
     */
    public static function getAllWithCommission(
        $includeUsers = FALSE,
        $includedUsersEnabledStatus = NULL,
        $includeLocationAddresses = FALSE
    ){
        $userGroups = UserGroup::with(['commission', 'location'])
            ->get()
            ->where('commission', '!==', null)
            ->values()
            ->all();

        if ($includeUsers === TRUE) {
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
                    ->listUsers(NULL, $includedUsersEnabledStatus);

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

        // By default, the UserGroup will be attaching the related Location data...
        // Check to see if this Location's Address data will also be included
        if ($includeLocationAddresses) {
            $userGroups = collect($userGroups)
                ->transform(function($userGroup){
                    $userGroup->location = (new LocationHelper())->formatLocationData(
                        $userGroup->location,
                        FALSE,
                        TRUE,
                        TRUE
                    );

                    return $userGroup;
                })
                ->all();
        }

        return $userGroups;
    }

}
