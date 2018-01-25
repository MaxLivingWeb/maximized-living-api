<?php

namespace App\Http\Controllers;

use App\Address;
use App\AddressType;
use App\CognitoUser;
use App\Location;
use App\UserGroup;
use App\User;
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
                'permissions.*' => 'nullable|string|distinct|exists:user_permissions,key',
                'business.name' => 'required'
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

            // Setup Shopify Customer initial params
            $shopifyCustomerData = [
                'email'      => $validatedData['email'],
                'first_name' => $validatedData['firstName'],
                'last_name'  => $validatedData['lastName']
            ];

            if (isset($validatedData['phone'])) {
                $shopifyCustomerData['phone'] = $validatedData['phone'];
            }

            // Get User Addresses (which will be saved to Shopify Customer account)
            $shopifyAddresses = [];

            //user is associated to a location
            if(isset($validatedData['groupName'])) {
                $userGroup = UserGroup::with(['commission', 'location'])
                    ->where('group_name', $validatedData['groupName'])
                    ->firstOrFail();
                $location = Location::with('userGroup')->findOrFail($userGroup->location->id);
                $locationAddresses = $location->addresses()->get()->toArray();

                // Get Address info for this Selected Location, and save that to Shopify Customer
                $shopifyAddresses = array_merge($shopifyAddresses, collect($locationAddresses)
                    ->transform(function($address) use($shopifyCustomerData, $validatedData){
                        return $this->formatAddressForShopifyCustomer(
                            $shopifyCustomerData,
                            $address,
                            $validatedData['business']['name']
                        );
                    })
                    ->all()
                );

                // Set first address in array to be the default
                // Note: MOST Locations should only have 1 address associated with them anyway.
                $shopifyAddresses[0]->default = true;

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

                // Request includes a wholesale shipping address, attach the address to the associate group
                if($request->has('wholesale.shipping')) {
                    $wholesaleShippingAddress = Address::create([
                        'address_1' => $request->input('wholesale.shipping.address_1'),
                        'address_2' => $request->input('wholesale.shipping.address_2'),
                        'zip_postal_code' => $request->input('wholesale.shipping.zip_postal_code') ?? '',
                        'city_id'   => intval($request->input('wholesale.shipping.city_id')),
                        'latitude' => 0,
                        'longitude' => 0
                    ]);

                    $shopifyAddresses[] = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $wholesaleShippingAddress,
                        $validatedData['business']['name']
                    );

                    $wholesaleShippingAddress->groups()->attach(
                        $userGroup->id,
                        ['address_type_id' => AddressType::firstOrCreate(['name' => 'Wholesale Shipping'])->id]
                    );
                }

                // Request includes a wholesale billing address, attach the address to the associate group
                if($request->has('wholesale.billing')) {
                    $wholesaleBillingAddress = Address::create([
                        'address_1' => $request->input('wholesale.billing.address_1'),
                        'address_2' => $request->input('wholesale.billing.address_2'),
                        'zip_postal_code' => $request->input('wholesale.billing.zip_postal_code') ?? '',
                        'city_id'   => intval($request->input('wholesale.billing.city_id')),
                        'latitude' => 0,
                        'longitude' => 0
                    ]);

                    $shopifyAddresses[] = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $wholesaleBillingAddress,
                        $validatedData['business']['name']
                    );

                    $wholesaleBillingAddress->groups()->attach(
                        $userGroup->id,
                        ['address_type_id' => AddressType::firstOrCreate(['name' => 'Wholesale Billing'])->id]
                    );
                }

                // Request includes a commission billing address, attach the address to the associate group
                if($request->has('commission.billing')) {
                    $commissionBillingAddress = Address::create([
                        'address_1' => $request->input('commission.billing.address_1'),
                        'address_2' => $request->input('commission.billing.address_2'),
                        'zip_postal_code' => $request->input('commission.billing.zip_postal_code') ?? '',
                        'city_id'   => intval($request->input('commission.billing.city_id')),
                        'latitude' => 0,
                        'longitude' => 0
                    ]);

                    $shopifyAddresses[] = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $commissionBillingAddress,
                        $validatedData['business']['name']
                    );

                    $commissionBillingAddress->groups()->attach(
                        $userGroup->id,
                        ['address_type_id' => AddressType::firstOrCreate(['name' => 'Commission Billing'])->id]
                    );
                }

                // By default, use the Wholesale Shipping address as the default. Otherwise, just use the first in the array.
                if (isset($wholesaleShippingAddress)) {
                    foreach ($shopifyAddresses as $i => $address) {
                        if ($address->zip === $wholesaleShippingAddress['zip_postal_code']) {
                            $shopifyAddresses[$i]->default = true;
                            break;
                        }
                    }
                }
                else {
                    $shopifyAddresses[0]->default = true;
                }
            }

            // Update Shopify Customer params
            $shopifyCustomerData['addresses'] = $shopifyAddresses;

            $defaultAddress = collect($shopifyAddresses)
                ->where('default', true)
                ->first();

            if ($defaultAddress) {
                $shopifyCustomerData['default_address'] = $defaultAddress;
            }

            // Add customer to Shopify
            // IMPORTANT: creating a Shopify customer should be the LAST step of the user creation process.
            // If any previous step fails, we roll back the account creation to prevent 'account already exists' errors.
            // We CANNOT DO THIS for Shopify customers. Creating a Shopify customer should always be the FINAL STEP.
            $shopifyCustomer = $shopify->getOrCreateCustomer($shopifyCustomerData);

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

        try {
            return response()->json(User::structureUser($cognito->getUser($id)));
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
        $cognito = new CognitoHelper();
        $shopify = new ShopifyHelper();

        try {
            $validatedData = $request->validate([
                'first_name'    => 'required',
                'last_name'     => 'required',
                'phone'         => 'nullable',
                'permissions'   => 'nullable|array|min:1',
                'permissions.*' => 'nullable|string|distinct|exists:user_permissions,key',
                'business.name' => 'required'
            ]);

            $user = new CognitoUser($id);

            // Update user addresses in API database
            $userGroup = $user->group();
            $addresses = $userGroup->location->addresses
                ?? $userGroup->addresses
                ?? [];

            // Wholesaler Shipping Addresses
            if(!empty($request->input('wholesale.shipping'))) {
                $wholesaleShippingAddress = $addresses
                    ->filter($this->_getAddressByType([4]))
                    ->first();

                if(!empty($wholesaleShippingAddress)){
                    $wholesaleShippingAddress->fill($request->input('wholesale.shipping'));
                    $wholesaleShippingAddress->save();
                }
            }

            // Wholesaler Billing Address
            if(!empty($request->input('wholesale.billing'))) {
                $wholesaleBillingAddress = $addresses
                    ->filter($this->_getAddressByType([5]))
                    ->first();

                if(!empty($wholesaleBillingAddress)){
                    $wholesaleBillingAddress->fill($request->input('wholesale.billing'));
                    $wholesaleBillingAddress->save();
                }
            }

            // Commission Billing address
            if(!empty($request->input('commission.billing'))){
                $commissionBillingAddress = $addresses
                    ->filter($this->_getAddressByType([6]))
                    ->first();

                if(!empty($commissionBillingAddress)){
                    $commissionBillingAddress->fill($request->input('commission.billing'));
                    $commissionBillingAddress->save();
                }
            }

            // Update permissions (for Cognito user)
            if(isset($validatedData['permissions'])) {
                $cognito->updateUserAttribute('custom:permissions', implode(',', $validatedData['permissions']), $request->id);
            }
            else {
                $cognito->removeUserAttribute(['custom:permissions'], $request->id);
            }

            // Get Shopify ID from Cognito user
            $cognitoUser = $cognito->getUser($id);
            $shopifyId = collect($cognitoUser['UserAttributes'])
                ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
                ->first()['Value'];

            // Basic Shopify Customer data to be updated...
            $shopifyCustomerData = [
                'id'         => $shopifyId,
                'first_name' => $validatedData['first_name'],
                'last_name'  => $validatedData['last_name']
            ];

            if(!is_null($validatedData['phone'])) {
                $shopifyCustomerData['phone'] = $validatedData['phone'];
            }

            // Shopify Addresses to be updated...
            $shopifyAddresses = collect($addresses)
                ->transform(function($address) use($shopifyCustomerData, $validatedData){
                    return $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $address,
                        $validatedData['business']['name']
                    );
                })
                ->all();

            // By default, use the Wholesale Shipping address as the default. Otherwise, just use the first in the array.
            if (isset($wholesaleShippingAddress)) {
                foreach ($shopifyAddresses as $i => $address) {
                    if ($address->zip === $wholesaleShippingAddress['zip_postal_code']) {
                        $shopifyAddresses[$i]->default = true;
                        break;
                    }
                }
            }
            else {
                $shopifyAddresses[0]->default = true;
            }

            $shopifyCustomerData['addresses'] = $shopifyAddresses;

            $defaultAddress = collect($shopifyAddresses)
                ->where('default', true)
                ->first();

            if ($defaultAddress) {
                $shopifyCustomerData['default_address'] = $defaultAddress;
            }

            // Save updates for Shopify Customer
            $shopify->updateCustomer($shopifyCustomerData);

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

        try {
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

    private function formatAddressForShopifyCustomer(
        $shopifyCustomerData,
        $address,
        $businessName = null,
        $default = false
    ){
        return (object)[
            'address1'      => $address['address_1'],
            'address2'      => $address['address_2'],
            'zip'           => $address['zip_postal_code'],
            'city'          => $address['city']['name'],
            'province'      => $address['region']['name'],
            'province_code' => $address['region']['abbreviation'],
            'country'       => $address['country']['name'],
            'country_code'  => $address['country']['abbreviation'],
            'company'       => $businessName,
            'first_name'    => $shopifyCustomerData['first_name'],
            'last_name'     => $shopifyCustomerData['last_name'],
            'name'          => $shopifyCustomerData['first_name'].' '.$shopifyCustomerData['last_name'],
            'phone'         => $shopifyCustomerData['phone'] ?? null,
            'default'       => $default
        ];
    }
}
