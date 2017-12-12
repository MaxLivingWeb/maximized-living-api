<?php

namespace App\Http\Controllers;

use App\Address;
use App\AddressType;
use App\UserGroup;
use App\Helpers\CognitoHelper;
use App\Helpers\ShopifyHelper;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Aws\Exception\AwsException;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function listUsers()
    {
        $cognito = new CognitoHelper();
        try {
            $result = $cognito->listUsers();

            if(is_null($result)) {
                return response()->json('no users', 404);
            }

            return response()->json($result);
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

    }

    public function addUser(Request $request)
    {
        $cognito = new CognitoHelper();
        $shopify = new ShopifyHelper();

        try {
            $fields = [
                'email'         => 'required|email',
                'password'      => 'required|min:8',
                'firstName'     => 'required',
                'lastName'      => 'required',
                'phone'         => 'nullable',
                'legacyId'      => 'nullable|integer',
                'commission.id' => 'nullable|integer',
                'discountCode'  => 'nullable|integer',
                'groupName'     => 'nullable',
                'permissions'   => 'nullable|array|min:1',
                'permissions.*' => 'nullable|string|distinct|exists:user_permissions,key'
            ];

            //body includes a wholesale billing address, validate it
            if($request->has('wholesale.billing')) {
                $fields = array_merge($fields, [
                    'wholesale.billing.address_1' => 'required',
                    'wholesale.billing.address_2' => 'nullable',
                    'wholesale.billing.city_id'   => 'required'
                ]);
            }

            //body includes a wholesale shipping address, validate it
            if($request->has('wholesale.shipping')) {
                $fields = array_merge($fields, [
                    'wholesale.shipping.address_1' => 'required',
                    'wholesale.shipping.address_2' => 'nullable',
                    'wholesale.shipping.city_id'   => 'required'
                ]);
            }

            //body includes a commission billing address, validate it
            if($request->has('commission.billing')) {
                $fields = array_merge($fields, [
                    'commission.billing.address_1' => 'required',
                    'commission.billing.address_2' => 'nullable',
                    'commission.billing.city_id'   => 'required'
                ]);
            }

            $validatedData = $request->validate($fields);

            //Add user to Cognito
            $cognitoUser = $cognito->createUser(
                $validatedData['email'],
                $validatedData['password']
            );

            //user is associated to a location
            if(isset($validatedData['groupName'])) {
                $cognito->addUserToGroup(
                    $cognitoUser->get('User')['Username'],
                    $validatedData['groupName']
                );

                $location = UserGroup::with(['location'])
                    ->where('group_name', $validatedData['groupName'])
                    ->firstOrFail();
                if (!is_null($location)) {
                    $params['group_name_display'] = $location->location->name;
                }
            }
            //user is not associated to a location
            else {
                //Create a group for just this associate
                $tempGroup = $cognito->createGroup(
                    'user.' . $validatedData['email'],
                    'group for ' . $validatedData['email']
                );
                $params = [
                    'group_name' => $tempGroup['GroupName'],
                    'group_name_display' => $validatedData['firstName'].' '.$validatedData['lastName']
                ];

                if(isset($validatedData['legacyId'])) {
                    $params['legacy_affiliate_id'] = $validatedData['legacyId'];
                }

                if(isset($validatedData['commission']['id'])) {
                    $params['commission_id'] = $validatedData['commission']['id'];
                }

                if(isset($validatedData['discountCode'])) {
                    $params['discount_id'] = $validatedData['discountCode'];
                }

                $userGroup = UserGroup::create($params);

                $cognito->addUserToGroup(
                    $cognitoUser->get('User')['Username'],
                    $tempGroup['GroupName']
                );

                //request includes a wholesale shipping address, attach the address to the associate group
                if($request->has('wholesale.shipping')) {
                    $shippingAddress = Address::create([
                        'address_1' => $request->input('wholesale.shipping.address_1'),
                        'address_2' => $request->input('wholesale.shipping.address_2'),
                        'city_id'   => intval($request->input('wholesale.shipping.city_id'))
                    ]);

                    $shippingAddress->groups()->attach(
                        $userGroup->id,
                        ['address_type_id' => AddressType::firstOrCreate(['name' => 'Wholesale Shipping'])->id]
                    );
                }

                //request includes a wholesale billing address, attach the address to the associate group
                if($request->has('wholesale.billing')) {
                    $billingAddress = Address::create([
                        'address_1' => $request->input('wholesale.billing.address_1'),
                        'address_2' => $request->input('wholesale.billing.address_2'),
                        'city_id'   => intval($request->input('wholesale.billing.city_id'))
                    ]);

                    $billingAddress->groups()->attach(
                        $userGroup->id,
                        ['address_type_id' => AddressType::firstOrCreate(['name' => 'Wholesale Billing'])->id]
                    );
                }

                //request includes a commission billing address, attach the address to the associate group
                if($request->has('commission.billing')) {
                    $commissionBillingAddress = Address::create([
                        'address_1' => $request->input('commission.billing.address_1'),
                        'address_2' => $request->input('commission.billing.address_2'),
                        'city_id'   => intval($request->input('commission.billing.city_id'))
                    ]);

                    $commissionBillingAddress->groups()->attach(
                        $userGroup->id,
                        ['address_type_id' => AddressType::firstOrCreate(['name' => 'Commission Billing'])->id]
                    );
                }
            }

            $customer = [
                'email'      => $validatedData['email'],
                'first_name' => $validatedData['firstName'],
                'last_name'  => $validatedData['lastName']
            ];

            if(isset($validatedData['phone'])) {
                $customer['phone'] = $validatedData['phone'];
            }

            //Add customer to Shopify
            $shopifyCustomer = $shopify->getOrCreateCustomer($customer);

            //Save Shopify ID to Cognito user attribute
            $cognito->updateUserAttribute(
                env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'),
                strval($shopifyCustomer->id),
                $validatedData['email']
            );

            if(isset($validatedData['permissions'])) {
                //attach permissions to user
                $cognito->updateUserAttribute(
                    'custom:permissions',
                    implode(',', $validatedData['permissions']),
                    $validatedData['email']
                );
            }

            return response()->json();
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch(ClientException $e) {
            $msg = $e->getMessage();
            if($e->hasResponse()) {
                $msg = $e->getResponse()->getBody()->getContents();
            }
            return response()->json([$msg], 500);
        }
        catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function getUser($id)
    {
        $cognito = new CognitoHelper();
        $shopify = new ShopifyHelper();

        try {
            $cognitoUser = $cognito->getUser($id);

            $res = (object) [
                'id'    => $cognitoUser->get('Username'),
                'email' => collect($cognitoUser['UserAttributes'])
                    ->where('Name', 'email')
                    ->first()['Value'],
                'user_status' => $cognitoUser->get('UserStatus')
            ];

            $shopifyId = collect($cognitoUser['UserAttributes'])
                ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
                ->first()['Value'];

            $affiliateId = collect($cognitoUser['UserAttributes'])
                ->where('Name', 'custom:affiliateId')
                ->first()['Value'];

            $shopifyCustomer = $shopify->getCustomer($shopifyId);

            $res->shopify_id = $shopifyCustomer->id;
            $res->referred_affiliate_id = is_null($affiliateId) ? $affiliateId : intval($affiliateId);
            $res->first_name = $shopifyCustomer->first_name;
            $res->last_name = $shopifyCustomer->last_name;
            $res->phone = $shopifyCustomer->phone;
            $res->addresses = $shopifyCustomer->addresses;

            $userGroups = $cognito->getGroupsForUser($id);

            if($userGroups->isNotEmpty()) {
                $res->affiliate = UserGroup::with(['commission', 'location'])
                    ->where('group_name', $userGroups->first()['GroupName'])
                    ->first();
            }

            $permissions = collect($cognitoUser['UserAttributes'])->where('Name', 'custom:permissions')->first();
            if(!is_null($permissions)) {
                $res->permissions = explode(',', $permissions['Value']);
            }

            return response()->json($res);
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'shopify_id' => 'required',
                'first_name' => 'required',
                'last_name'  => 'required',
                'phone'      => 'nullable',
                'group'      => 'nullable',
                'permissions'   => 'nullable|array|min:1',
                'permissions.*' => 'nullable|string|distinct|exists:user_permissions,key'
            ]);

            //update user in Shopify
            $shopify = new ShopifyHelper();

            $customer = [
                'id'         => $validatedData['shopify_id'],
                'first_name' => $validatedData['first_name'],
                'last_name'  => $validatedData['last_name']
            ];
            if(!is_null($validatedData['phone'])) {
                $customer['phone'] = $validatedData['phone'];
            }

            $shopify->updateCustomer($customer);

            $cognito = new CognitoHelper();

            if(isset($validatedData['group'])) {
                //Remove user from existing groups
                $userGroups = $cognito->getGroupsForUser($request->id);
                foreach ($userGroups as $group) {
                    $cognito->removeUserFromGroup($request->id, $group['GroupName']);
                }

                //add user to new group
                $cognito->addUserToGroup($request->id, $validatedData['group']);
            }

            //update permissions
            if(isset($validatedData['permissions'])) {
                $cognito->updateUserAttribute('custom:permissions', implode(',', $validatedData['permissions']), $request->id);
            }
            else {
                //no permissions, remove them from user
                $cognito->removeUserAttribute(['custom:permissions'], $request->id);
            }

            return response()->json();
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function linkToAffiliate($id, $affiliateId)
    {
        try {
            $cognito = new CognitoHelper();

            $cognito->updateUserAttribute(
                'custom:affiliateId',
                $affiliateId,
                $id
            );
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
    }
}
