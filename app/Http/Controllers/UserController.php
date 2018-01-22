<?php

namespace App\Http\Controllers;

use App\Address;
use App\AddressType;
use App\CognitoUser;
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
                'wholesaler'    => 'nullable|boolean',
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
                $userGroup = UserGroup::where('group_name', $validatedData['groupName'])->first();

                $userGroup->addUser($cognitoUser->get('User')['Username']);
            }
            //user is not associated to a location
            else {
                $params = [
                    'group_name' => 'user.' . $validatedData['email'],
                    'group_name_display' => $validatedData['firstName'].' '.$validatedData['lastName']
                ];

                if(isset($validatedData['legacyId'])) {
                    $params['legacy_affiliate_id'] = $validatedData['legacyId'];
                }

                if(isset($validatedData['commission']['id'])) {
                    $params['commission_id'] = $validatedData['commission']['id'];
                }

                if(isset($validatedData['wholesaler'])) {
                    $params['wholesaler'] = $validatedData['wholesaler'];
                }

                $userGroup = UserGroup::create($params);

                $userGroup->addUser($cognitoUser->get('User')['Username']);

                //request includes a wholesale shipping address, attach the address to the associate group
                if($request->has('wholesale.shipping')) {
                    $shippingAddress = Address::create([
                        'address_1' => $request->input('wholesale.shipping.address_1'),
                        'address_2' => $request->input('wholesale.shipping.address_2'),
                        'zip_postal_code' => $request->input('wholesale.shipping.zip_postal_code') ?? '',
                        'city_id'   => intval($request->input('wholesale.shipping.city_id')),
                        'latitude' => 0,
                        'longitude' => 0
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
                        'zip_postal_code' => $request->input('wholesale.billing.zip_postal_code') ?? '',
                        'city_id'   => intval($request->input('wholesale.billing.city_id')),
                        'latitude' => 0,
                        'longitude' => 0
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
                        'zip_postal_code' => $request->input('commission.billing.zip_postal_code') ?? '',
                        'city_id'   => intval($request->input('commission.billing.city_id')),
                        'latitude' => 0,
                        'longitude' => 0
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
            if(!empty($cognitoUser->get('User')['Username'])){
                $cognito->deleteUser($cognitoUser->get('User')['Username']);
            }
            $msg = $e->getMessage();
            if($e->hasResponse()) {
                $msg = $e->getResponse()->getBody()->getContents();
            }
            return response()->json([$msg], 500);
        }
        catch (ValidationException $e) {
            if(!empty($cognitoUser->get('User')['Username'])){
                $cognito->deleteUser($cognitoUser->get('User')['Username']);
            }
            return response()->json($e->errors(), 400);
        }
        catch (\Exception $e) {
            if(!empty($cognitoUser->get('User')['Username'])){
                $cognito->deleteUser($cognitoUser->get('User')['Username']);
            }
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

            $user = new CognitoUser($id);
            $userGroup = $user->group();
            if(!is_null($userGroup)) {
                $res->affiliate = $userGroup;
            }

            $permissions = collect($cognitoUser['UserAttributes'])->where('Name', 'custom:permissions')->first();
            if(NULL !== $permissions) {
                $res->permissions = explode(',', $permissions['Value']);
            }

            $res->addresses = $userGroup->location->addresses
                           ?? $userGroup->addresses
                           ?? [];

            return response()->json($res);
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function updateUser(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'first_name' => 'required',
                'last_name'  => 'required',
                'phone'      => 'nullable',
            ]);

            $user = new CognitoUser($id);

            // update user addresses in API database
            $userGroup = $user->group();
            $addresses = $userGroup->location->addresses
                ?? $userGroup->addresses
                ?? [];

            // wholesaler addresses
            if(!empty($request->input('wholesale.shipping'))) {
                $shippingAddress = $addresses
                    ->filter($this->_getAddressByType([4]))
                    ->first();
                if(!empty($shippingAddress)){
                    $shippingAddress->fill($request->input('wholesale.shipping'));
                    $shippingAddress->save();
                }
            }

            if(!empty($request->input('wholesale.billing'))) {
                $billingAddress = $addresses
                    ->filter($this->_getAddressByType([5]))
                    ->first();
                if(!empty($billingAddress)){
                    $billingAddress->fill($request->input('wholesale.billing'));
                    $billingAddress->save();
                }
            }

            // commission addresses
            if(!empty($request->input('commission.billing'))){
                $billingAddress = $addresses
                    ->filter($this->_getAddressByType([6]))
                    ->first();
                if(!empty($billingAddress)){
                    $billingAddress->fill($request->input('commission.billing'));
                    $billingAddress->save();
                }
            }

            // update user in Cognito / get Shopify ID from Cognito user
            $cognito = new CognitoHelper();
            $cognitoUser = $cognito->getUser($id);
            $shopifyId = collect($cognitoUser['UserAttributes'])
                ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
                ->first()['Value'];

            // update user in Shopify
            $customer = [
                'id'         => $shopifyId,
                'first_name' => $validatedData['first_name'],
                'last_name'  => $validatedData['last_name']
            ];
            if(NULL !== $validatedData['phone']) {
                $customer['phone'] = $validatedData['phone'];
            }

            $shopify = new ShopifyHelper();
            $shopify->updateCustomer($customer);

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

    public function affiliate($id)
    {
        try {
            $user = new CognitoUser($id);
            return response()->json($user->group());
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function delete($id)
    {
        $cognito = new CognitoHelper();
        $shopify = new ShopifyHelper();

        try {
            $cognitoUser = $cognito->getUser($id);
            $shopifyId = collect($cognitoUser['UserAttributes'])
                ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
                ->first()['Value'];

            if(!empty($shopifyId)){
                $shopify->deleteCustomer($shopifyId);
            }

            $cognito->deleteUser($id);

            return response()->json();
        } catch (AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    private function _getAddressByType(array $types = [])
    {
        return function ($address) use ($types)
        {
            return in_array($address->type->id, $types);
        };
    }
}
