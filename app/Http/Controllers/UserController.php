<?php

namespace App\Http\Controllers;

use App\Address;
use App\AddressType;
use App\CognitoUser;
use App\Location;
use App\UserGroup;
use App\User;
use App\Helpers\CognitoUserHelper;
use App\Helpers\CognitoHelper;
use App\Helpers\ShopifyHelper;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * List Users from Cognito
     * @param null|string $groupName (Get Cognito users by a specific UserGroup. To get ALL Cognito users, enter "ALL_COGNITO_USERS")
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    public function listUsers($groupName = NULL)
    {
        return CognitoUserHelper::listUsers($groupName);
    }

    /**
     * Cognito Reporting Helper function
     * Find all Cognito users that share the same email address (since emails can be saved as uppercase or lowercase into the system...)
     * @return array|void
     */
    public function listCognitoUsersWithDuplicateInstances()
    {
        return CognitoUserHelper::listCognitoUsersWithDuplicateInstances();
    }

    /**
     * Cognito Reporting Helper function
     * Find all Cognito users that have uppercased email addresses
     * @return array|void
     */
    public function listCognitoUsersWithUppercasedEmails()
    {
        return CognitoUserHelper::listCognitoUsersWithUppercasedEmails();
    }

    /**
     * Add New User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUser(Request $request)
    {
        $cognito = new CognitoHelper();
        $shopify = new ShopifyHelper();

        try {
            $fields = [
                'email'               => 'required|email',
                'password'            => 'required|min:8',
                'first_name'          => 'required',
                'last_name'           => 'required',
                'phone'               => 'nullable',
                'legacyId'            => 'nullable|integer',
                'commission.id'       => 'nullable|integer',
                'wholesaler'          => 'nullable|boolean',
                'selectedLocation.id' => 'nullable',
                'permissions'         => 'nullable|array|min:1',
                'permissions.*'       => 'nullable|string|distinct|exists:user_permissions,key',
                'business.name'       => 'required',
                'custom_attributes'   => 'nullable|array|min:1'
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
                'first_name' => $validatedData['first_name'],
                'last_name'  => $validatedData['last_name']
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
                $userGroup = UserGroup::with(['commission', 'location'])->findOrFail($location->userGroup->id);
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

                $userGroup->addUser($cognitoUser->get('User')['Username']);
            }
            // User is not associated to a location
            else {
                $userGroup = UserGroup::createGroupForUser(
                    $validatedData,
                    $cognitoUser->get('User')['Username']
                );

                // Attach default address
                $defaultAddress = null;
                if ($request->has('defaultAddress')) {
                    $defaultAddress = $this->addAddressToDatabase(
                        $request,
                        'defaultAddress',
                        'Default Address', // Do NOT make this be "Main Location", as that will conflict with Affiliate Locations "Main Location" address data when switching usergroups (from a location usergroup to an individual usergroup)
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

            // User Permissions - Update in Cognito
            if (isset($validatedData['permissions'])) {
                //attach permissions to user
                $cognito->updateUserAttribute(
                    'custom:permissions',
                    implode(',', $validatedData['permissions']),
                    $validatedData['email']
                );
            }

            // User Attributes - Update in Cognito
            if (isset($validatedData['custom_attributes'])) {
                //attach attributes to user
                $cognito->updateUserAttribute(
                    'custom:attributes',
                    implode(',', $validatedData['custom_attributes']),
                    $validatedData['email']
                );
            }

            // Update Addresses saved in DB, so they are mapped to these Shopify Customer Addresses
            // Then while Editing Users, we can re-use the same Shopify Addresses than re-creating new ones
            $this->updateShopifyAttributesToAddresses(
                [
                    $defaultAddress ?? null,
                    $wholesaleBillingAddress ?? null,
                    $wholesaleShippingAddress ?? null,
                    $commissionBillingAddress ?? null
                ],
                $shopifyCustomer->addresses,
                $shopifyAddresses,
                $userGroup
            );

            return response()->json();
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
     * Update Existing User by providing their Cognito user id
     * Note: To update specific data groups for user during this request, attach parameter "datagroup". Example - This is used to update "basic_details", which is the users first & last Name. If nothing is provided for this parameter, update ALL user data.
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(Request $request, $id)
    {
        $cognito = new CognitoHelper();
        $shopify = new ShopifyHelper();

        try {
            // Update only specific data sets based on passed query params
            // TODO: Probably update this logic, so this can all just be handled in the one updateUser() method
            $queryParams = $request->query();
            if (isset($queryParams['datagroup']) && $queryParams['datagroup'] === 'basic_details') {
                return $this->updateUserBasicDetails($request, $id);
            }

            // No specific data groups were specified, so update all user data...
            // Continue validation as usual
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
                'business.name'       => 'required',
                'custom_attributes'   => 'nullable|array|min:1'
            ]);

            $user = new CognitoUser($id);

            // Get Shopify ID from Cognito user
            $cognitoUser = $cognito->getUser($id);
            $email = collect($cognitoUser['UserAttributes'])
                ->where('Name', 'email')
                ->first()['Value'];
            $shopifyId = (int)collect($cognitoUser['UserAttributes'])
                ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
                ->first()['Value'];

            // create a usergroup for this user if it does not exist
            $userGroup = $user->group();
            if(empty($userGroup)) {
                $userGroup = UserGroup::createGroupForUser(
                    array_merge($validatedData, ['email' => $email]),
                    $id
                );
            }

            // Update user addresses in API database
            $addresses = $userGroup->location->addresses
                ?? $userGroup->addresses
                ?? collect();

            // Basic Shopify Customer data to be updated...
            $shopifyCustomerData = [
                'id'         => $shopifyId,
                'first_name' => $validatedData['first_name'],
                'last_name'  => $validatedData['last_name']
            ];

            if(!is_null($validatedData['phone'])) {
                $shopifyCustomerData['phone'] = $validatedData['phone'];
            }

            // Double check this user is apart of the AffiliateUsers Cognito user-group
            $cognitoUserGroups = $cognito->getGroupsForUser($id);
            if (!collect($cognitoUserGroups)->pluck('GroupName')->contains(env('AWS_COGNITO_AFFILIATE_USER_GROUP_NAME'))) {
                $cognito->addUserToGroup($id, env('AWS_COGNITO_AFFILIATE_USER_GROUP_NAME'));
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

                // $userGroup variable value has been updated to be the new LocationUserGroup
                $userGroup = $locationUserGroup;
            }
            // User is not associated to a location
            else {
                // Transfer User to a different UserGroup (if previously in a Location UserGroup)
                if (!empty($userGroup->location)) {
                    $userGroup->deleteUser($id);

                    // Create new userGroup and add user here
                    $userGroup = UserGroup::create([
                        'group_name' => 'user.' . $email,
                        'group_name_display' => $validatedData['first_name'].' '.$validatedData['last_name']
                    ]);
                    $userGroup->addUser($id);
                }

                // Default Address
                $defaultAddress = null;
                if(!empty($request->input('defaultAddress'))) {
                    $defaultAddress = $addresses
                        ->filter($this->_getAddressByType([7]))
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
                            'Default Address', // Do NOT make this be "Main Location", as that will conflict with Affiliate Locations "Main Location" address data when switching usergroups (from a location usergroup to an individual usergroup)
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

            // Update attributes (for Cognito user)
            if(isset($validatedData['custom_attributes'])) {
                $cognito->updateUserAttribute('custom:attributes', implode(',', $validatedData['custom_attributes']), $request->id);
            }
            else {
                $cognito->removeUserAttribute(['custom:attributes'], $request->id);
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
            $addressesToUpdate = (isset($locationAddresses) && !empty($locationAddresses))
                ? $locationAddresses
                : [
                    $defaultAddress ?? null,
                    $wholesaleBillingAddress ?? null,
                    $wholesaleShippingAddress ?? null,
                    $commissionBillingAddress ?? null
                ]
            ;
            $this->updateShopifyAttributesToAddresses(
                $addressesToUpdate,
                $shopifyCustomer->addresses,
                $shopifyAddresses,
                $userGroup
            );

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

    /**
     * Update Cognito User's email address
     * Note: This method will not properly handle updating user addresses across all platforms. A developer will still have to manually update the email across everything else (Shopify, Wordpress, etc)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserEmailAddress(Request $request)
    {
        try {
            $cognito = new CognitoHelper();

            $validatedData = $request->validate([
                'email' => 'required|email'
            ]);

            $cognito->updateUserEmailAddress($validatedData['email'], $request->id);

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

    /**
     * Update only basic user details
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserBasicDetails(Request $request, $id)
    {
        $cognito = new CognitoHelper();
        $shopify = new ShopifyHelper();

        try {
            $validatedData = $request->validate([
                'first_name' => 'required',
                'last_name'  => 'required',
                'phone'      => 'nullable'
            ]);

            $cognitoUser = $cognito->getUser($id);
            $shopifyId = (int)collect($cognitoUser['UserAttributes'])
                ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
                ->first()['Value'];

            // Basic Shopify Customer data to be updated...
            $shopifyCustomerData = [
                'id'         => $shopifyId,
                'first_name' => $validatedData['first_name'],
                'last_name'  => $validatedData['last_name'],
                'phone'      => $validatedData['phone'] ?? ''
            ];

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

    /**
     * Attach or Detach any address data for this Shopify Customer
     * @param $customAddresses (Custom Addresses saved to the API)
     * @param $shopifyCustomerAddresses (Addresses that are saved to the Shopify Customer)
     * @param $shopifyAddresses (Addresses that were just updated)
     * @param $userGroup (Can not attach Shopify Address IDs on addresses associated to a Location)
     */
    private function updateShopifyAttributesToAddresses(
        $customAddresses,
        $shopifyCustomerAddresses,
        $shopifyAddresses,
        $userGroup = null
    ){
        if (count($customAddresses) > 0 && count($shopifyCustomerAddresses) > 0) {
            foreach ($customAddresses as $customAddress) {
                if (is_null($customAddress)) {
                    continue;
                }

                // Attach this Address, since it was mapped to the $shopifyAddresses array that was updated for this Shopify Customer
                // Note: Can not attach shopify address attributes on addresses related to an affiliate location
                if ((!empty($userGroup) && empty($userGroup->location))
                    && (
                        collect($shopifyAddresses)
                            ->pluck('privatedata')
                            ->where('custom_address_id', $customAddress['id'])
                            ->isNotEmpty()
                        && is_null($customAddress['shopify_id'])
                    )
                ) {
                    $this->attachShopifyAttributesToAddress($customAddress, $shopifyCustomerAddresses, $shopifyAddresses);
                }
            }

            // Detach this Address, since it was not mapped to the $shopifyAddresses array that was updated for this Shopify Customer
            collect($shopifyCustomerAddresses)
                ->filter(function($shopifyCustomerAddress){
                    return !$shopifyCustomerAddress->default;
                })
                ->reject(function($shopifyCustomerAddress) use($shopifyAddresses){
                    return collect($shopifyAddresses)
                        ->pluck('publicdata')
                        ->filter(function($shopifyAddress) use($shopifyCustomerAddress){
                            if (!is_null($shopifyAddress->id)) {
                                return $shopifyAddress->id === $shopifyCustomerAddress->id;
                            }
                            return ($shopifyCustomerAddress->company === $shopifyAddress->company
                                && $shopifyCustomerAddress->address1 === $shopifyAddress->address1
                                && $shopifyCustomerAddress->address2 === $shopifyAddress->address2
                                && $shopifyCustomerAddress->city === $shopifyAddress->city
                                && $shopifyCustomerAddress->province === $shopifyAddress->province
                                && $shopifyCustomerAddress->country === $shopifyAddress->country
                                && str_replace(' ', '',$shopifyCustomerAddress->zip) === str_replace(' ', '', $shopifyAddress->zip)
                            );
                        })
                        ->all();
                })
                ->each(function($shopifyCustomerAddress){
                    $customAddress = Address::where('shopify_id', $shopifyCustomerAddress->id)->first();
                    $this->detachShopifyAddressFromUser($customAddress, $shopifyCustomerAddress);
                });
        }
    }

    /**
     * Attach the Shopify Address ID to our Custom Address that is saved into the API
     * @param $customAddresses (Custom Addresses saved to the API)
     * @param $shopifyCustomerAddresses (Addresses that are saved to the Shopify Customer)
     * @param $shopifyAddresses (Addresses that were just updated)
     */
    private function attachShopifyAttributesToAddress($customAddress, $shopifyCustomerAddresses, $shopifyAddresses)
    {
        if (!is_null($customAddress) && count($shopifyCustomerAddresses) > 0 && count($shopifyAddresses) > 0) {
            foreach ($shopifyCustomerAddresses as $shopifyCustomerAddress) {
                $shopifyCustomerAddressToUpdate = collect($shopifyAddresses)
                    ->filter(function($shopifyAddress) use($customAddress) {
                        return $shopifyAddress->privatedata->custom_address_id === $customAddress['id'];
                    })
                    ->transform(function($shopifyAddress) use($shopifyCustomerAddress){
                        if ($shopifyCustomerAddress->company === $shopifyAddress->publicdata->company
                            && $shopifyCustomerAddress->address1 === $shopifyAddress->publicdata->address1
                            && $shopifyCustomerAddress->address2 === $shopifyAddress->publicdata->address2
                            && $shopifyCustomerAddress->city === $shopifyAddress->publicdata->city
                            && $shopifyCustomerAddress->province === $shopifyAddress->publicdata->province
                            && $shopifyCustomerAddress->country === $shopifyAddress->publicdata->country
                            && str_replace(' ', '',$shopifyCustomerAddress->zip) === str_replace(' ', '', $shopifyAddress->publicdata->zip)
                        ) {
                            return $shopifyCustomerAddress;
                        }
                    })
                    ->first();

                if (!is_null($shopifyCustomerAddressToUpdate)) {
                    if ($shopifyCustomerAddressToUpdate->id) {
                        $customAddress->attachShopifyAddressID($shopifyCustomerAddressToUpdate->id);
                    }
                    if ($shopifyCustomerAddressToUpdate->default) {
                        $customAddress->attachShopifyAddressDefaultValue($shopifyCustomerAddressToUpdate->default);
                    }
                }
            }
        }
    }

    /**
     * Remove the Shopify Address ID from our Custom Address that is saved into the API
     * @param $customAddress (Custom Address saved to the API)
     * @param $shopifyAddress (Address that was just updated to Shopify)
     */
    private function detachShopifyAddressFromUser($customAddress, $shopifyAddress)
    {
        if ((isset($shopifyAddress->id) && !is_null($shopifyAddress->id))
            && (isset($shopifyAddress->customer_id) && !is_null($shopifyAddress->customer_id))
        ) {
            // Delete this address from being associated to this Shopify Customer
            $shopify = new ShopifyHelper();
            $shopify->deleteCustomerAddress((array)$shopifyAddress);

            // Detach saved ShopifyAddress ID from Custom Address
            if (!is_null($customAddress)) {
                $customAddress->resetShopifyAddressID();
                $customAddress->resetShopifyAddressDefaultValue();
            }
        }
    }

    /**
     * Get User by provided Cognito User ID
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser($id)
    {
        $cognito = new CognitoHelper();

        try {
            $user = $cognito->getUser($id);
            return response()->json(User::structureUser($user));
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Link this User to an Affiliate User from the provided Affiliate ID
     * @param $id
     * @param $affiliateId
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Get User's Affiliate Group based on the provided Cognito User ID
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Delete User based on the provided Cognito User ID
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Helper Function -- Get Address data by provided Address Type ID
     * @param array $types
     * @return \Closure
     */
    private function _getAddressByType(array $types = [])
    {
        return function ($address) use ($types)
        {
            return in_array($address->type->id, $types);
        };
    }

    /**
     * Insert User's Address to Database
     * @param $request
     * @param $fieldName
     * @param $fieldDescription
     * @param $userGroup
     * @return null
     */
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

    /**
     * Format Address data to be passed into Shopify Request
     * @param $shopifyCustomerData
     * @param null $businessName
     * @param array $address
     * @param bool $default
     * @return \stdClass
     */
    private function formatAddressForShopifyCustomer(
        $shopifyCustomerData,
        $businessName = null,
        $address = [],
        $default = false
    ){

        // Dumb fix to ensure that data is entered correctly into Shopify
        $country = $address['country']['name'] ?? null;
        if ($country === 'United States of America') {
            $country = 'United States';
        }

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
                'country'       => $country ?? '',
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

    /**
     * Validate that Shopify Address being added to Shopify Customer is completely unique
     * @param $currentAddress
     * @param $addresses
     * @return bool
     */
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
