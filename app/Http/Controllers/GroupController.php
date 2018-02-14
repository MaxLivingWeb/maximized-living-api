<?php

namespace App\Http\Controllers;

use App\Address;
use App\AddressType;
use App\UserGroup;
use App\Helpers\CognitoHelper;
use App\Helpers\TextHelper;
use Aws\Exception\AwsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Helpers\ShopifyHelper;
use Illuminate\Support\Facades\Cache;

class GroupController extends Controller
{
    public function all()
    {
        return UserGroup::all();
    }

    public function allWithLocation()
    {
        return UserGroup::with(['location.addresses.city.region.country'])
            ->get()
            ->where('location', '!==', null)
            ->values()
            ->all();
    }

    public function getById($id)
    {
        return UserGroup::with('commission')->findOrFail($id);
    }

    public function getUsersById($id)
    {
        $userGroup = UserGroup::findorFail($id);
        return $userGroup->listUsers();
    }

    public function allWithCommission(Request $request)
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

            $shopifyUsers = (new ShopifyHelper(1440))
                ->getCustomers(
                    collect($allUsers)
                        ->pluck('shopify_id')
                );

            foreach($userGroups as $userGroup) {
                $userGroup->loadUsers(
                    $allUsers,
                    $shopifyUsers
                );
            }
        }

        return $userGroups;
    }

    public function getByName(Request $request)
    {
        $name = TextHelper::fixEscapeForSpecialCharacters($request->input('name'));
        return UserGroup::with(['commission', 'location'])->where('group_name', $name)->firstOrFail();
    }

    public function add(Request $request)
    {
        try {
            $fields = [
                'group_name'         => 'required',
                'group_name_display' => 'required',
                'premium'            => 'nullable|boolean',
                'commission.id'      => 'nullable|integer',
            ];

            //body includes a wholesale billing address, validate it
            if($request->has('wholesale.billing')) {
                $fields = array_merge($fields, [
                    'wholesale.billing.address_1' => 'required',
                    'wholesale.billing.city_id'   => 'required'
                ]);
            }

            //body includes a wholesale shipping address, validate it
            if($request->has('wholesale.shipping')) {
                $fields = array_merge($fields, [
                    'wholesale.shipping.address_1' => 'required',
                    'wholesale.shipping.city_id'   => 'required'
                ]);
            }

            //body includes a commission billing address, validate it
            if($request->has('commission.billing')) {
                $fields = array_merge($fields, [
                    'commission.billing.address_1' => 'required',
                    'commission.billing.city_id'   => 'required'
                ]);
            }

            $request->validate($fields);

            $commission_id = null;
            if (!is_null($request->input('commission.id'))) {
                $commission_id = intval($request->input('commission.id'));
            }

            $location_id = null;
            if (!is_null($request->input('location_id'))) {
                $location_id = intval($request->input('location_id'));
            }

            $wholesaler = false;
            if (!is_null($request->input('wholesaler'))) {
                $wholesaler = boolval($request->input('wholesaler'));
            }

            $premium = false;
            if (!is_null($request->input('premium'))) {
                $premium = boolval($request->input('premium'));
            }

            $event_promoter = false;
            if (!is_null($request->input('event_promoter'))) {
                $event_promoter = boolval($request->input('event_promoter'));
            }

            if($request->has('wholesale.shipping') && !is_null($location_id)) {
                $shippingAddress = Address::create([
                    'address_1' => $request->input('wholesale.shipping.address_1'),
                    'address_2' => $request->input('wholesale.shipping.address_2') ?? '',
                    'zip_postal_code' => $request->input('wholesale.shipping.zip_postal_code') ?? '',
                    'city_id'   => intval($request->input('wholesale.shipping.city_id')),
                    'latitude' => 0,
                    'longitude' => 0
                ]);

                $shippingAddress->groups()->attach(
                    $location_id,
                    ['address_type_id' => AddressType::firstOrCreate(['name' => 'Wholesale Shipping'])->id]
                );
            }

            if($request->has('wholesale.billing') && !is_null($location_id)) {
                $billingAddress = Address::create([
                    'address_1' => $request->input('wholesale.billing.address_1'),
                    'address_2' => $request->input('wholesale.billing.address_2') ?? '',
                    'zip_postal_code' => $request->input('wholesale.billing.zip_postal_code') ?? '',
                    'city_id'   => intval($request->input('wholesale.billing.city_id')),
                    'latitude' => 0,
                    'longitude' => 0
                ]);

                $billingAddress->groups()->attach(
                    $location_id,
                    ['address_type_id' => AddressType::firstOrCreate(['name' => 'Wholesale Billing'])->id]
                );
            }

            if($request->has('commission.billing') && !is_null($location_id)) {
                $billingAddress = Address::create([
                    'address_1' => $request->input('commission.billing.address_1'),
                    'address_2' => $request->input('commission.billing.address_2')  ?? '',
                    'zip_postal_code' => $request->input('commission.billing.zip_postal_code') ?? '',
                    'city_id'   => intval($request->input('commission.billing.city_id')),
                    'latitude' => 0,
                    'longitude' => 0
                ]);

                $billingAddress->groups()->attach(
                    $location_id,
                    ['address_type_id' => AddressType::firstOrCreate(['name' => 'Commission Billing'])->id]
                );
            }

            $cognito = new CognitoHelper();
            $cognito->createGroup(
                $request->input('group_name'),
                $request->input('group_name_display')
            );

            return UserGroup::create([
                'group_name'         => $request->input('group_name'),
                'group_name_display' => $request->input('group_name_display'),
                'wholesaler'         => $wholesaler,
                'commission_id'      => $commission_id,
                'location_id'        => $location_id,
                'premium'            => $premium,
                'event_promoter'     => $event_promoter,
                'maxtv_token'        => bin2hex(random_bytes(32))
            ]);
        }
        catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }
        catch (AwsException $e) {
            return response()->json($e->getAwsErrorMessage(), 400);
        }
    }

    public function update($id, Request $request)
    {
        $group = UserGroup::findOrFail($id);

        $premium = false;
        if (!is_null($request->input('premium'))) {
            $premium = boolval($request->input('premium'));
        }

        $event_promoter = false;
        if (!is_null($request->input('event_promoter'))) {
            $event_promoter = boolval($request->input('event_promoter'));
        }

        $commission_id = null;
        if (!is_null($request->input('commission.id'))) {
            $commission_id = intval($request->input('commission.id'));
        }

        $group->premium = $premium;
        $group->event_promoter = $event_promoter;
        $group->commission_id = $commission_id;

        $group->save();
    }

    public function delete($id)
    {
        $group = UserGroup::findOrFail($id);
        $group->delete();
    }
}
