<?php

namespace App\Http\Controllers;

use App\{Address,AddressType,UserGroup};
use App\Helpers\{TextHelper,UserGroupHelper,TrackingHelper};
use App\Location;
use Aws\Exception\AwsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Exception;

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

    public function getById(Request $request)
    {
        $userGroup = UserGroup::with('commission')->findOrFail($request->id);

        if ((bool)$request->input('include_users') === TRUE) {
            $userGroup->assignUsersToUserGroup();
        }

        return $userGroup;
    }

    /**
     * Retrieves a list of all Cognito users associated with a given UserGroup.
     * @param Request $request
     * @param integer $id The ID of the location to retrieve users for.
     * @return array
     */
    public function getUsersById(Request $request, $id)
    {
        $userGroup = UserGroup::findorFail($id);
        $enabledStatus = $request->input('enabled_status') ?? null;
        return $userGroup->listUsers($enabledStatus);
    }

    /**
     * Get All Affiliate UserGroups
     * @param Request $request
     * @return array
     */
    public function allWithCommission(Request $request)
    {
        $includeUsers = (bool)$request->input('include_users');
        $includedUsersEnabledStatus = $request->input('included_users_enabled_status') ?? null; //Note: Can only be assigned if `include_users` is also set to true.
        $includeLocationAddresses = (bool)$request->input('include_location_addresses');

        return UserGroupHelper::getAllWithCommission(
            $includeUsers,
            $includedUsersEnabledStatus,
            $includeLocationAddresses
        );
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

            try {
                DB::beginTransaction();

                $userGroup = UserGroup::create([
                    'group_name'         => $request->input('group_name'),
                    'group_name_display' => $request->input('group_name_display'),
                    'wholesaler'         => $wholesaler,
                    'commission_id'      => $commission_id,
                    'location_id'        => $location_id,
                    'premium'            => $premium,
                    'event_promoter'     => $event_promoter,
                    'maxtv_token'        => bin2hex(random_bytes(32))
                ]);

                $trackingHelper = new TrackingHelper();
                // Update regional count
                $location = Location::with('addresses.city.region')
                    ->where('id', $location_id)
                    ->firstOrFail();
                $trackingHelper->updateRegionalCount($location->addresses[0]->city->region->id);

                // Update market count
                $location = Location::with('addresses.city.market')
                    ->where('id', $location_id)
                    ->firstOrFail();
                    if(!is_null($location->addresses[0]->city->market)) {
                        $trackingHelper->updateMarketCount($location->addresses[0]->city->market->id);
                    }
                DB::commit();

                return $userGroup;
            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }
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
        try {
            DB::beginTransaction();

            $group = UserGroup::findOrFail($id);

            $wholesaler = false;
            if (!is_null($request->input('wholesaler'))) {
                $wholesaler = (bool)$request->input('wholesaler');
            }

            $premium = false;
            if (!is_null($request->input('premium'))) {
                $premium = (bool)$request->input('premium');
            }

            $event_promoter = false;
            if (!is_null($request->input('event_promoter'))) {
                $event_promoter = (bool)$request->input('event_promoter');
            }

            $commission_id = null;
            if (!is_null($request->input('commission.id'))) {
                $commission_id = (int)$request->input('commission.id');
            }

            $group->wholesaler = $wholesaler;
            $group->premium = $premium;
            $group->event_promoter = $event_promoter;
            $group->commission_id = $commission_id;

            $group->save();

            // Update regional & market count
            $trackingHelper = new TrackingHelper();
            $location = Location::with('addresses.city.region')
                ->where('id', $group->location_id)
                ->firstOrFail();
            $trackingHelper->updateRegionalCount($location->addresses[0]->city->region->id);

            $location = Location::with('addresses.city.market')
                ->where('id', $group->location_id)
                ->firstOrFail();
            if(!is_null($location->addresses[0]->city->market)) {
                $trackingHelper->updateMarketCount($location->addresses[0]->city->market->id);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            \Log::error($e);
            throw $e;
        }
    }
}
