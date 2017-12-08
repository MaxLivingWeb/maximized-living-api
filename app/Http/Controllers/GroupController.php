<?php

namespace App\Http\Controllers;

use App\Address;
use App\AddressType;
use App\Helpers\CognitoHelper;
use App\UserGroup;
use Aws\Exception\AwsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        $userGroup = UserGroup::with('commission')->findOrFail($id);

        $cognito = new CognitoHelper();
        $users = $cognito->listUsersForGroup($userGroup->group_name);

        return $users;
    }

    public function allWithCommission()
    {
        return UserGroup::with(['commission'])
            ->get()
            ->where('commission', '!==', null)
            ->values()
            ->all();
    }

    public function getByName(Request $request)
    {
        return UserGroup::with(['commission', 'location'])->where('group_name', $request->input('name'))->firstOrFail();
    }

    public function add(Request $request)
    {
        try {
            $fields = [
                'group_name' => 'required',
                'premium'    => 'nullable|boolean'
            ];

            $request->validate($fields);

            $commission_id = null;
            if (!is_null($request->input('commission_id'))) {
                $commission_id = intval($request->input('commission_id'));
            }

            $location_id = null;
            if (!is_null($request->input('location_id'))) {
                $location_id = intval($request->input('location_id'));
            }

            $discount_id = null;
            if (!is_null($request->input('discount_id'))) {
                $discount_id = intval($request->input('discount_id'));
            }

            $premium = false;
            if (!is_null($request->input('premium'))) {
                $premium = boolval($request->input('premium'));
            }

            if($request->has('wholesale.shipping') && !is_null($location_id)) {
                $shippingAddress = Address::create([
                    'address_1' => $request->input('wholesale.shipping.address_1'),
                    'address_2' => $request->input('wholesale.shipping.address_2'),
                    'city_id'   => intval($request->input('wholesale.shipping.city_id'))
                ]);

                $shippingAddress->locations()->attach($location_id, ['address_type_id' => AddressType::firstOrCreate(['name' => 'Shipping'])->id]);
            }

            if($request->has('wholesale.billing') && !is_null($location_id)) {
                $billingAddress = Address::create([
                    'address_1' => $request->input('wholesale.billing.address_1'),
                    'address_2' => $request->input('wholesale.billing.address_2'),
                    'city_id'   => intval($request->input('wholesale.billing.city_id'))
                ]);

                $billingAddress->locations()->attach($location_id, ['address_type_id' => AddressType::firstOrCreate(['name' => 'Billing'])->id]);
            }

            $cognito = new CognitoHelper();
            $cognito->createGroup($request->input('group_name'), '');

            return UserGroup::create([
                'group_name'    => $request->input('group_name'),
                'discount_id'   => $discount_id,
                'commission_id' => $commission_id,
                'location_id'   => $location_id,
                'premium'       => $premium
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

        $group->discount_id = intval($request->input('discount_id'));

        $group->save();
    }

    public function delete($id)
    {
        $group = UserGroup::findOrFail($id);

        $group->delete();
    }
}
