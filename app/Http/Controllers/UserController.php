<?php

namespace App\Http\Controllers;

use App\{Address,AddressType,CognitoUser,Location,UserGroup,User};
use App\Helpers\{CognitoUserHelper,CognitoHelper,WordpressHelper};
use GuzzleHttp\Exception\ClientException;
use Aws\Exception\AwsException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * List Users from Cognito
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Support\Collection
     */
    public function listUsers(Request $request)
    {
        $request->validate([
            'created_on' => 'date',
            'created_before' => 'date',
            'created_after' => 'date'
        ]);

        $groupName = $request->input('group_name') ?? null;
        $enabledStatus = $request->input('enabled_status') ?? null;
        $createdOnDate = $request->input('created_on') !== null
            ? new Carbon(request()->input('created_on'))
            : null;
        $createdBeforeDate = $request->input('created_before') !== null
            ? new Carbon(request()->input('created_before'))
            : null;
        $createdAfterDate = $request->input('created_after') !== null
            ? new Carbon(request()->input('created_after'))
            : null;
        $permissions = $request->input('permissions') !== null
            ? explode(',', $request->input('permissions'))
            : null;

        return CognitoUserHelper::listUsers(
            $groupName,
            $enabledStatus,
            $createdOnDate,
            $createdBeforeDate,
            $createdAfterDate,
            $permissions
        );
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
     * Get User by provided Cognito User ID
     * @param string $id (Cognito User ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser($id)
    {
        $cognito = new CognitoHelper();

        try {
            $cognitoUser = $cognito->getUser($id);
            return response()->json(User::structureUser($cognitoUser));
        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Add New User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUser(Request $request)
    {
        $cognito = new CognitoHelper();

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

    /**
     * Update Existing User by providing their Cognito user id
     * Note: To update specific data groups for user during this request, attach parameter "datagroup". Example - This is used to update "basic_details", which is the users first & last Name. If nothing is provided for this parameter, update ALL user data.
     * @param Request $request
     * @param string $id (Cognito User ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUser(Request $request, $id)
    {
        $cognito = new CognitoHelper();

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

            // // Get Shopify ID from Cognito user
            // $cognitoUser = $cognito->getUser($id);
            // $email = collect($cognitoUser['UserAttributes'])
            //     ->where('Name', 'email')
            //     ->first()['Value'];
            // $shopifyId = (int)collect($cognitoUser['UserAttributes'])
            //     ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
            //     ->first()['Value'];

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

            $defaultShopifyAddress = collect($shopifyAddresses)
                ->pluck('publicdata')
                ->where('default', true)
                ->first();

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
     * Get User by provided Cognito User ID, and then create a brand new thirdparty account for this user by passing in the account 'type' parameter
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createThirdpartyAccountForUser(Request $request)
    {
        $cognito = new CognitoHelper();

        try {
            $validatedData = $request->validate([
                'type' => 'required'
            ]);

            $accountType = strtolower($validatedData['type']);

            // Create Wordpress Account for User
            if ($accountType === 'wordpress') {
                $wordpress = new WordpressHelper();

                // TODO: Validate that user doesn't currently have Wordpress account, and stop the request from continuing. Although it doesn't seem to override the current account at all, if one is already set....
                //...

                $cognitoUser = $cognito->getUser($request->id);
                $user = User::structureUser($cognitoUser);
                $userGroup = (new CognitoUser($user->id))->group();

                if (empty($userGroup)) {
                    return response()->json(['Unable to Create Wordpress Account for user. Please add user to Affiliate UserGroup.'], 202);
                }

                // Ensure user has correct permissions
                if (!in_array('public-website', $user->permissions)) {
                    $user->permissions[] = 'public-website';
                }
                $cognito->updateUserAttribute('custom:permissions', implode(',', $user->permissions), $request->id);

                // Create their account
                $wordpress->createUser([
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                    'vanity_website_ids' => [
                        (strval($userGroup->location->vanity_website_id)) ?? ''
                    ],
                ]);

                return response()->json(['Wordpress account created.']);
            }

            return response()->json(['No thirdparty account could be created from the provided account type "'.$accountType.'"']);

        }
        catch(AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Update Cognito User's permissions
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserPermissions(Request $request)
    {
        try {
            $cognito = new CognitoHelper();

            $validatedData = $request->validate([
                'permissions' => 'required'
            ]);

            $cognito->updateUserAttribute('custom:permissions', $validatedData['permissions'], $request->id);

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
     * Force Password Reset on Cognito User
     * @param string $id (Cognito User ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetUserPassword($id)
    {
        try {
            $cognito = new CognitoHelper();
            $cognito->resetUserPassword($id);
            $cognito->updateUserAttribute('custom:verificationState', 'AdminResetPassword', $id);

            return response()->json(['Admin Reset Password for Cognito User '. $id]);
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
     * @param string $id (Cognito User ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserBasicDetails(Request $request, $id)
    {
        $cognito = new CognitoHelper();

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
     * Reassign the Shopify ID attribute to this Cognito User
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserShopifyID(Request $request)
    {
        $cognito = new CognitoHelper();

        try {
            $cognito->updateUserAttribute('custom:shopifyId', $request->shopify_id, $request->id);

            return response()->json();
        }
        catch (AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Deactivate User based on the provided Cognito User ID
     * @param string $id (Cognito User ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivateUser($id)
    {
        $cognito = new CognitoHelper();

        try {
            $cognito->deactivateUser($id);

            return response()->json();
        }
        catch (AwsException $e) {
            return response()->json([$e->getAwsErrorMessage()], 500);
        }
        catch (\Exception $e) {
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
     * @param string $fieldName
     * @param string $fieldDescription
     * @param \App\UserGroup $userGroup
     * @return \App\Address|null
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
     * Validate that Shopify Address being added to Shopify Customer is completely unique
     * @param \stdClass $currentAddress (The address being compared against all the other addresses)
     * @param array $addresses
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
