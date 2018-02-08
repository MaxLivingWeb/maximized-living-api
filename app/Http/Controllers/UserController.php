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
use Illuminate\Support\Facades\Log;
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
                'email'               => 'required|email',
                'password'            => 'required|min:8',
                'firstName'           => 'required',
                'lastName'            => 'required',
                'phone'               => 'nullable',
                'legacyId'            => 'nullable|integer',
                'commission.id'       => 'nullable|integer',
                'wholesaler'          => 'nullable|boolean',
                'selectedLocation.id' => 'nullable',
                'permissions'         => 'nullable|array|min:1',
                'permissions.*'       => 'nullable|string|distinct|exists:user_permissions,key',
                'business.name'       => 'required'
            ];

            // user is not associated to a location, and they entered a default address...
            if($request->has('defaultAddress')) {
                $fields = array_merge($fields, [
                    'defaultAddress.address_1' => 'required',
                    'defaultAddress.address_2' => 'nullable',
                    'defaultAddress.city_id'   => 'required'
                ]);
            }

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
            $cognito->addUserToGroup($cognitoUser->get('User')['Username'], env('AWS_COGNITO_AFFILIATE_USER_GROUP_NAME'));

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

            // User is associated to a location
            if(isset($validatedData['selectedLocation']['id'])) {
                $locationId = (int)$validatedData['selectedLocation']['id'];
                $location = Location::with('userGroup')->findOrFail($locationId);
                $locationUserGroup = UserGroup::with(['commission', 'location'])->findOrFail($location->userGroup->id);
                $locationAddresses = $location->addresses()->get()->toArray();

                // Get Address info for this Selected Location, and save that to Shopify Customer
                $shopifyAddresses = array_merge($shopifyAddresses, collect($locationAddresses)
                    ->transform(function($address) use($shopifyCustomerData, $validatedData){
                        return $this->formatAddressForShopifyCustomer(
                            $shopifyCustomerData,
                            $validatedData['business']['name'],
                            $address
                        );
                    })
                    ->unique()
                    ->all()
                );

                // Set first address in array to be the default
                // Note: MOST Locations should only have 1 address associated with them anyway.
                if (!empty($shopifyAddresses)) {
                    $shopifyAddresses[0]->publicdata->default = true;
                }

                $locationUserGroup->addUser($cognitoUser->get('User')['Username']);
            }
            // User is not associated to a location
            else {
                $userGroupData = [
                    'group_name' => 'user.' . $validatedData['email'],
                    'group_name_display' => $validatedData['firstName'].' '.$validatedData['lastName']
                ];

                if(isset($validatedData['legacyId'])) {
                    $userGroupData['legacy_affiliate_id'] = $validatedData['legacyId'];
                }

                if(isset($validatedData['commission']['id'])) {
                    $userGroupData['commission_id'] = $validatedData['commission']['id'];
                }

                if(isset($validatedData['wholesaler'])) {
                    $userGroupData['wholesaler'] = $validatedData['wholesaler'];
                }

                $userGroup = UserGroup::create($userGroupData);
                $userGroup->addUser($cognitoUser->get('User')['Username']);

                // Attach default address
                $defaultAddress = null;
                if ($request->has('defaultAddress')) {
                    $defaultAddress = $this->addAddressToDatabase(
                        $request,
                        'defaultAddress',
                        'Main Location',
                        $userGroup
                    );

                    $shopifyAddress = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $validatedData['business']['name'],
                        $defaultAddress
                    );
                    if ($this->uniqueShopifyAddressData($shopifyAddress, $shopifyAddresses)) {
                        $shopifyAddresses[] = $shopifyAddress;
                    }
                }

                // Attach the Wholesale Shipping address to the associate group
                $wholesaleShippingAddress = null;
                if($request->has('wholesale.shipping')) {
                    $wholesaleShippingAddress = $this->addAddressToDatabase(
                        $request,
                        'wholesale.shipping',
                        'Wholesale Shipping',
                        $userGroup
                    );

                    $shopifyAddress = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $validatedData['business']['name'],
                        $wholesaleShippingAddress
                    );
                    if ($this->uniqueShopifyAddressData($shopifyAddress, $shopifyAddresses)) {
                        $shopifyAddresses[] = $shopifyAddress;
                    }
                }

                // Attach the Wholesale Billing address to the associate group
                $wholesaleBillingAddress = null;
                if($request->has('wholesale.billing')) {
                    $wholesaleBillingAddress = $this->addAddressToDatabase(
                        $request,
                        'wholesale.billing',
                        'Wholesale Billing',
                        $userGroup
                    );

                    $shopifyAddress = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $validatedData['business']['name'],
                        $wholesaleBillingAddress
                    );
                    if ($this->uniqueShopifyAddressData($shopifyAddress, $shopifyAddresses)) {
                        $shopifyAddresses[] = $shopifyAddress;
                    }
                }

                // Attach the Commission Billing address to the associate group
                $commissionBillingAddress = null;
                if($request->has('commission.billing')) {
                    $commissionBillingAddress = $this->addAddressToDatabase(
                        $request,
                        'commission.billing',
                        'Commission Billing',
                        $userGroup
                    );

                    $shopifyAddress = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $validatedData['business']['name'],
                        $commissionBillingAddress
                    );
                    if ($this->uniqueShopifyAddressData($shopifyAddress, $shopifyAddresses)) {
                        $shopifyAddresses[] = $shopifyAddress;
                    }
                }

                // If User has addresses associated, then set the default address
                // By default, use the Wholesale Shipping address as the default. Otherwise, just use the first in the array.
                if (!empty($shopifyAddresses)) {
                    if (!empty($wholesaleShippingAddress)) {
                        foreach ($shopifyAddresses as $i => $address) {
                            if ($address->privatedata->custom_address_id === $wholesaleShippingAddress['id']) {
                                $shopifyAddresses[$i]->publicdata->default = true;
                                break;
                            }
                        }
                    }
                    else {
                        $shopifyAddresses[0]->publicdata->default = true;
                    }
                }
            }

            // Save addresses to Shopify Customer
            // Note: We need to send the Business Name field over, since this field is validated as required
            $placeholderShopifyAddresses = [
                $this->formatAddressForShopifyCustomer(
                    $shopifyCustomerData,
                    $validatedData['business']['name']
                )
            ];

            $shopifyCustomerData['addresses'] = !empty($shopifyAddresses)
                ? collect($shopifyAddresses)->pluck('publicdata')
                : collect($placeholderShopifyAddresses)->pluck('publicdata');

            $defaultShopifyAddress = collect($shopifyAddresses)
                ->pluck('publicdata')
                ->where('default', true)
                ->first();

            if ($defaultShopifyAddress) {
                $shopifyCustomerData['default_address'] = $defaultShopifyAddress;
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

            if (isset($validatedData['permissions'])) {
                //attach permissions to user
                $cognito->updateUserAttribute(
                    'custom:permissions',
                    implode(',', $validatedData['permissions']),
                    $validatedData['email']
                );
            }

            // Update Addresses saved in DB, so they are mapped to these Shopify Customer Addresses
            // Then while Editing Users, we can re-use the same Shopify Addresses than re-creating new ones
            $this->attachShopifyAttributesToAddresses(
                [
                    $defaultAddress ?? null,
                    $wholesaleBillingAddress ?? null,
                    $wholesaleShippingAddress ?? null,
                    $commissionBillingAddress ?? null
                ],
                $shopifyCustomer->addresses,
                $shopifyAddresses
            );

            return response()->json([
                'ShopifyCustomer' => $shopifyCustomer
            ]);
        }
        catch(AwsException $e) {
            Log::error($e);
            return response()->json(
                $e->getAwsErrorMessage(),
                $e->getStatusCode()
            );
        }
        catch(ClientException $e) {
            Log::error($e);
            if(!empty($cognitoUser->get('User')['Username'])){
                $cognito->deleteUser($cognitoUser->get('User')['Username']);
            }
            $msg = $e->getMessage();
            if($e->hasResponse()) {
                $msg = $e->getResponse()->getBody()->getContents();
            }
            return response()->json(
                $msg,
                $e->getCode()
            );
        }
        catch (ValidationException $e) {
            Log::error($e);
            if(!empty($cognitoUser->get('User')['Username'])){
                $cognito->deleteUser($cognitoUser->get('User')['Username']);
            }
            return response()->json(
                $e->errors(),
                400
            );
        }
        catch (\Exception $e) {
            Log::error($e);
            if(!empty($cognitoUser->get('User')['Username'])){
                $cognito->deleteUser($cognitoUser->get('User')['Username']);
            }
            return response()->json(
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Attach the Shopify Address ID to our Custom Address that is saved into the API
     * @param $addresses (List of Address objects that need to attach the Shopify Address IDs)
     * @param $shopifyCustomerAddresses (Addresses saved to Shopify Customer)
     * @param $shopifyAddresses
     */
    private function attachShopifyAttributesToAddresses($addresses, $shopifyCustomerAddresses, $shopifyAddresses)
    {
        if (count($addresses) > 0 && count($shopifyCustomerAddresses) > 0) {
            foreach ($addresses as $address) {
                if (is_null($address)
                 || collect($shopifyAddresses)
                        ->pluck('privatedata')
                        ->where('custom_address_id', $address['id'])
                        ->isEmpty()
                 || !is_null($address['shopify_id'])
                ) {
                    continue;
                }

                $arrayIndex = (int)collect($shopifyAddresses)
                    ->pluck('privatedata')
                    ->where('custom_address_id', $address['id'])
                    ->keys()
                    ->first();

                if (!is_null($arrayIndex)) {
                    $shopifyAddressId = $shopifyCustomerAddresses[$arrayIndex]->id;
                    if ($shopifyAddressId) {
                        $address->attachShopifyAddressID($shopifyAddressId);
                    }

                    $shopifyAddressDefaultValue = $shopifyCustomerAddresses[$arrayIndex]->default;
                    if ($shopifyAddressDefaultValue) {
                        $address->attachShopifyAddressDefaultValue($shopifyAddressDefaultValue);
                    }
                }
            }
        }
    }

    /**
     * Remove the Shopify Address ID from our Custom Address that is saved into the API
     * @param $id
     * @param $shopifyAddress
     * @param $shopifyCustomerAddresses
     */
    private function detachShopifyAddressFromUser($id, $shopifyAddress, $shopifyCustomerAddresses)
    {
        if (count($shopifyCustomerAddresses) > 0
            && (
                isset($shopifyAddress->address->id) && !is_null($shopifyAddress->address->id)
            )
            && (
                isset($shopifyAddress->address->customer_id) && !is_null($shopifyAddress->address->customer_id)
            )
        ) {
            $address = Address::findOrFail($id);
            $address->resetShopifyAddressID();
            $address->resetShopifyAddressDefaultValue();

            // Delete this address from being associated to this Shopify Customer
            $shopify = new ShopifyHelper();
            $shopify->deleteCustomerAddress((array)$shopifyAddress);
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
                'first_name'          => 'required',
                'last_name'           => 'required',
                'phone'               => 'nullable',
                'legacyId'            => 'nullable|integer',
                'commission.id'       => 'nullable|integer',
                'wholesaler'          => 'nullable|boolean',
                'selectedLocation.id' => 'nullable',
                'permissions'         => 'nullable|array|min:1',
                'permissions.*'       => 'nullable|string|distinct|exists:user_permissions,key',
                'business.name'       => 'required'
            ]);

            $user = new CognitoUser($id);

            // Update user addresses in API database
            $userGroup = $user->group();

            $addresses = $userGroup->location->addresses
                ?? $userGroup->addresses
                ?? [];

            // Get Shopify ID from Cognito user
            $cognitoUser = $cognito->getUser($id);
            $shopifyId = collect($cognitoUser['UserAttributes'])
                ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
                ->first()['Value'];

            // Get current state for shopify customer
            $currentShopifyCustomer = $shopify->getCustomer($shopifyId);

            // Basic Shopify Customer data to be updated...
            $shopifyCustomerData = [
                'id'         => $shopifyId,
                'first_name' => $validatedData['first_name'],
                'last_name'  => $validatedData['last_name']
            ];

            if(!is_null($validatedData['phone'])) {
                $shopifyCustomerData['phone'] = $validatedData['phone'];
            }

            // Get User Addresses (which will be saved to Shopify Customer account)
            $shopifyAddresses = [];

            // User is associated to a location
            if(isset($validatedData['selectedLocation']['id'])) {
                $locationId = (int)$validatedData['selectedLocation']['id'];
                $location = Location::with('userGroup')->findOrFail($locationId);
                $locationUserGroup = UserGroup::with(['commission', 'location'])->findOrFail($location->userGroup->id);
                $locationAddresses = $location->addresses()->get()->toArray();

                // Get Address info for this Selected Location, and save that to Shopify Customer
                $shopifyAddresses = array_merge($shopifyAddresses, collect($locationAddresses)
                    ->transform(function($address) use($shopifyCustomerData, $validatedData){
                        return $this->formatAddressForShopifyCustomer(
                            $shopifyCustomerData,
                            $validatedData['business']['name'],
                            $address
                        );
                    })
                    ->unique()
                    ->all()
                );

                // Set first address in array to be the default
                // Note: MOST Locations should only have 1 address associated with them anyway.
                if (!empty($shopifyAddresses)) {
                    $shopifyAddresses[0]->publicdata->default = true;
                }

                // Transfer User to a different UserGroup
                if (!empty($userGroup)) {
                    $userGroup->deleteUser($id);
                }
                $locationUserGroup->addUser($id);
            }
            // User is not associated to a location
            else {

                // Transfer User to a different UserGroup (if previously in a Location UserGroup)
                if (!empty($userGroup->location)) {
                    if (!empty($userGroup)) {
                        $userGroup->deleteUser($id);
                    }
                    $singleUserGroup = UserGroup::create([
                        'group_name' => 'user.' . $validatedData['email'],
                        'group_name_display' => $validatedData['first_name'].' '.$validatedData['last_name']
                    ]);
                    $singleUserGroup->addUser($id);
                }

                // TODO: Clean up how we handle custom address data, this is all replicated code basically... Just need time to improve this setup.
                // Default Address
                $defaultAddress = null;
                if(!empty($request->input('defaultAddress'))) {
                    $defaultAddress = $addresses
                        ->filter($this->_getAddressByType([1]))
                        ->first();

                    if(!empty($defaultAddress)){
                        // Update defaultAddress
                        $defaultAddress->fill($request->input('defaultAddress'));
                        $defaultAddress->save();
                    }
                    else {
                        // Add new defaultAddress
                        $defaultAddress = $this->addAddressToDatabase(
                            $request,
                            'defaultAddress',
                            'Main Location',
                            $userGroup
                        );
                    }

                    // Add this address to list of Shopify Addresses
                    $shopifyAddress = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $validatedData['business']['name'],
                        $defaultAddress
                    );
                    if ($this->uniqueShopifyAddressData($shopifyAddress, $shopifyAddresses)) {
                        $shopifyAddresses[] = $shopifyAddress;
                    }
//                    else {
//                        $this->detachShopifyAddressFromUser(
//                            $defaultAddress['id'],
//                            $shopifyAddress,
//                            $currentShopifyCustomer->addresses
//                        );
//                    }
                }

                // Wholesaler Shipping Address
                $wholesaleShippingAddress = null;
                if(!empty($request->input('wholesale.shipping'))) {
                    $wholesaleShippingAddress = $addresses
                        ->filter($this->_getAddressByType([4]))
                        ->first();

                    if(!empty($wholesaleShippingAddress)){
                        // Update wholesale.shipping address
                        $wholesaleShippingAddress->fill($request->input('wholesale.shipping'));
                        $wholesaleShippingAddress->save();
                    }
                    else {
                        // Add new wholesale.shipping address
                        $wholesaleShippingAddress = $this->addAddressToDatabase(
                            $request,
                            'wholesale.shipping',
                            'Wholesale Shipping',
                            $userGroup
                        );
                    }

                    // Add this address to list of Shopify Addresses
                    $shopifyAddress = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $validatedData['business']['name'],
                        $wholesaleShippingAddress
                    );
                    if ($this->uniqueShopifyAddressData($shopifyAddress, $shopifyAddresses)) {
                        $shopifyAddresses[] = $shopifyAddress;
                    }
//                    else {
//                        $this->detachShopifyAddressFromUser(
//                            $wholesaleShippingAddress['id'],
//                            $shopifyAddress,
//                            $currentShopifyCustomer->addresses
//                        );
//                    }
                }

                // Wholesaler Billing Address
                $wholesaleBillingAddress = null;
                if(!empty($request->input('wholesale.billing'))) {
                    $wholesaleBillingAddress = $addresses
                        ->filter($this->_getAddressByType([5]))
                        ->first();

                    if(!empty($wholesaleBillingAddress)){
                        // Update wholesale.billing address
                        $wholesaleBillingAddress->fill($request->input('wholesale.billing'));
                        $wholesaleBillingAddress->save();
                    }
                    else {
                        // Add new wholesale.shipping address
                        $wholesaleBillingAddress = $this->addAddressToDatabase(
                            $request,
                            'wholesale.billing',
                            'Wholesale Billing',
                            $userGroup
                        );
                    }

                    // Add this address to list of Shopify Addresses
                    $shopifyAddress = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $validatedData['business']['name'],
                        $wholesaleBillingAddress
                    );
                    if ($this->uniqueShopifyAddressData($shopifyAddress, $shopifyAddresses)) {
                        $shopifyAddresses[] = $shopifyAddress;
                    }
//                    else {
//                        $this->detachShopifyAddressFromUser(
//                            $wholesaleBillingAddress['id'],
//                            $shopifyAddress,
//                            $currentShopifyCustomer->addresses
//                        );
//                    }
                }

                // Commission Billing address
                $commissionBillingAddress = null;
                if(!empty($request->input('commission.billing'))){
                    $commissionBillingAddress = $addresses
                        ->filter($this->_getAddressByType([6]))
                        ->first();

                    if(!empty($commissionBillingAddress)){
                        // Update commission.billing address
                        $commissionBillingAddress->fill($request->input('commission.billing'));
                        $commissionBillingAddress->save();
                    }
                    else {
                        // Add new commission.billing address
                        $commissionBillingAddress = $this->addAddressToDatabase(
                            $request,
                            'commission.billing',
                            'Commission Billing',
                            $userGroup
                        );
                    }

                    // Add this address to list of Shopify Addresses
                    $shopifyAddress = $this->formatAddressForShopifyCustomer(
                        $shopifyCustomerData,
                        $validatedData['business']['name'],
                        $commissionBillingAddress
                    );
                    if ($this->uniqueShopifyAddressData($shopifyAddress, $shopifyAddresses)) {
                        $shopifyAddresses[] = $shopifyAddress;
                    }
//                    else {
//                        $this->detachShopifyAddressFromUser(
//                            $commissionBillingAddress['id'],
//                            $shopifyAddress,
//                            $currentShopifyCustomer->addresses
//                        );
//                    }
                }

                // If User has addresses associated, then set the default address
                // By default, use the Wholesale Shipping address as the default. Otherwise, just use the first in the array.
                if (!empty($shopifyAddresses)) {
                    $defaultAddressID = collect($addresses)
                        ->where('shopify_default',true)
                        ->pluck('id')
                        ->first()
                    ?? $wholesaleShippingAddress['id']
                    ?? false;

                    if ($defaultAddressID) {
                        foreach ($shopifyAddresses as $i => $address) {
                            if ($address->privatedata->custom_address_id === $defaultAddressID) {
                                $shopifyAddresses[$i]->publicdata->default = true;
                                break;
                            }
                        }
                    }
                    else {
                        $shopifyAddresses[0]->publicdata->default = true;
                    }
                }

                // Update `legacy_affiliate_id` value to User
                $userGroup->legacy_affiliate_id = !empty($validatedData['legacyId']) ? (int)$validatedData['legacyId'] : null;
                // Update `commission.id` value to User
                $userGroup->commission_id = !empty($validatedData['commission']['id']) ? (int)$validatedData['commission']['id'] : null;
                // Update `wholesaler` value to User
                $userGroup->wholesaler = !empty($validatedData['wholesaler']) ? (bool)$validatedData['wholesaler'] : false;
                // Save updates
                $userGroup->save();
            }

            // Update permissions (for Cognito user)
            if(isset($validatedData['permissions'])) {
                $cognito->updateUserAttribute('custom:permissions', implode(',', $validatedData['permissions']), $request->id);
            }
            else {
                $cognito->removeUserAttribute(['custom:permissions'], $request->id);
            }

            // Save addresses to Shopify Customer
            // Note: We need to send the Business Name field over, since this field is validated as required
            $placeholderShopifyAddresses = [
                $this->formatAddressForShopifyCustomer(
                    $shopifyCustomerData,
                    $validatedData['business']['name']
                )
            ];

            $shopifyCustomerData['addresses'] = !empty($shopifyAddresses)
                ? collect($shopifyAddresses)->pluck('publicdata')
                : collect($placeholderShopifyAddresses)->pluck('publicdata');

            $defaultShopifyAddress = collect($shopifyAddresses)
                ->pluck('publicdata')
                ->where('default', true)
                ->first();

            if ($defaultShopifyAddress) {
                $shopifyCustomerData['default_address'] = $defaultShopifyAddress;
            }

            // Save updates for Shopify Customer
            $shopifyCustomer = $shopify->updateCustomer($shopifyCustomerData);

            // Update Addresses saved in DB, so they are mapped to these Shopify Customer Addresses
            // Then while Editing Users, we can re-use the same Shopify Addresses than re-creating new ones
            $this->attachShopifyAttributesToAddresses(
                [
                    $defaultAddress ?? null,
                    $wholesaleBillingAddress ?? null,
                    $wholesaleShippingAddress ?? null,
                    $commissionBillingAddress ?? null
                ],
                $shopifyCustomer->addresses,
                $shopifyAddresses
            );

            return response()->json([
                'ShopifyCustomer' => $shopifyCustomer,
                'ShopifyAddresses' => $shopifyAddresses
            ]);
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

    private function addAddressToDatabase(
        $request,
        $fieldName,
        $fieldDescription,
        $userGroup
    ) {
        $address = null;

        if($request->has($fieldName)) {
            $address = Address::create([
                'address_1'       => $request->input($fieldName.'.address_1'),
                'address_2'       => $request->input($fieldName.'.address_2'),
                'zip_postal_code' => $request->input($fieldName.'.zip_postal_code') ?? '',
                'city_id'         => (int)$request->input($fieldName.'.city_id'),
                'latitude'        => 0,
                'longitude'       => 0
            ]);

            $address->groups()->attach(
                $userGroup->id,
                ['address_type_id' => AddressType::firstOrCreate(['name' => $fieldDescription])->id]
            );
        }

        return $address;
    }

    private function formatAddressForShopifyCustomer(
        $shopifyCustomerData,
        $businessName = null,
        $address = [],
        $default = false
    ){
        return (object)[
            // Private fields - will not be sent to Shopify, but used within the setup of this controller
            'privatedata' => (object)[
                'custom_address_id' => $address['id'] ?? null
            ],
            // Public fields - pass data to Shopify
            'publicdata' => (object)[
                'id'            => (isset($address['shopify_id']) && !empty($address['shopify_id'])) ? $address['shopify_id'] : null,
                'customer_id'   => (isset($shopifyCustomerData['id'])) ? (int)$shopifyCustomerData['id'] : null,
                'address1'      => $address['address_1'] ?? '',
                'address2'      => $address['address_2'] ?? '',
                'zip'           => $address['zip_postal_code'] ?? '',
                'city'          => $address['city']['name'] ?? '',
                'province'      => $address['region']['name'] ?? '',
                'province_code' => $address['region']['abbreviation'] ?? '',
                'country'       => $address['country']['name'] ?? '',
                'country_code'  => $address['country']['abbreviation'] ?? '',
                'company'       => $businessName ?? 'N/A',
                'first_name'    => $shopifyCustomerData['first_name'],
                'last_name'     => $shopifyCustomerData['last_name'],
                'name'          => $shopifyCustomerData['first_name'].' '.$shopifyCustomerData['last_name'],
                'phone'         => $shopifyCustomerData['phone'] ?? null,
                'default'       => $default
            ]
        ];
    }

    private function uniqueShopifyAddressData($currentAddress, $addresses)
    {
        if (count($addresses) === 0) {
            return true;
        }

        $matches = 0;

        $keysToIgnore = ['id', 'default', 'customer_id'];

        $numberOfArrayKeys = count(
            array_keys(
                collect($currentAddress->publicdata)
                    ->reject(function($value, $key) use($keysToIgnore){
                        return in_array($key, $keysToIgnore);
                    })
                    ->all()
            )
        );

        foreach($addresses as $address){
            $numberOfSimilarities = 0;

            foreach($address->publicdata as $key => $value){
                if (in_array($key, $keysToIgnore)) {
                    continue;
                }
                if (in_array($value, (array)$currentAddress->publicdata)){
                    $numberOfSimilarities++;
                }
            }

            if ($numberOfSimilarities === $numberOfArrayKeys) {
                $matches++;
            }
        }

        return $matches === 0;
    }
}
