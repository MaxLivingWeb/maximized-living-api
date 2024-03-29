<?php

namespace App\Helpers;

use App\CognitoUser;
use App\User;
use Aws\Sdk;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Cache;

class CognitoHelper
{
    /**
     * The AWS SDK client.
     *
     * @var \Aws\Sdk
     */
    private $client;

    /**
     * The number of minutes to cache results for. Defaults to the .env value (or 5 minutes if there is no .env value).
     *
     * @var integer
     */
    protected $cacheTime;

    /**
     * How long to sleep between groups of listUser queries.
     *
     * @var integer
     */
    protected $listUsersSleepTime = 1;

    /**
     * How many listUser queries to run before sleeping.
     *
     * @var integer
     */
    protected $listUsersQueryGroupSize = 3;

    /**
     * CognitoHelper constructor.
     *
     * @param integer|null $cacheTime
     */
    public function __construct($cacheTime = NULL)
    {
        $sharedConfig = [
            'region'  => 'us-east-2',
            'version' => '2016-04-18'
        ];

        $this->cacheTime = $cacheTime ?? env('AWS_COGNITO_CACHE_TIME', 5);

        $sdk = new Sdk($sharedConfig);
        $this->client = $sdk->createCognitoIdentityProvider();
    }

    /**
     * Retrieves a single user from Cognito.
     *
     * @param string $id The Cognito ID of the user to retrieve.
     * @return mixed
     */
    public function getUser($id)
    {
        try {
            return $this->client->adminGetUser([
                'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
                'Username' => $id
            ]);
        }
        catch(AwsException $e) {
            if($e->getStatusCode() !== 400) { // user not found
                abort(
                    $e->getStatusCode(),
                    $e->getAwsErrorMessage()
                );
            }

            return;
        }
    }

    /**
     * Returns an array of users from Cognito.
     *
     * @param null|string $groupName (The name of the group to get users for. If no group name is provided, will default to the .env affiliate group name. To return all users - pass the value 'ALL_COGNITO_USERS')
     * @param null|string $enabledStatus (Get Cognito users by a specific enabled status. 'enabled' (default), 'disabled', 'any'
     * @param null|\Carbon\Carbon $createdOnDate Carbonized Date - User was created exactly on this date ("yyyy-mm-dd")
     * @param null|\Carbon\Carbon $createdBeforeDate Carbonized Date - User was created before this date ("yyyy-mm-dd")
     * @param null|\Carbon\Carbon $createdAfterDate Carbonized Date - User was created after this date ("yyyy-mm-dd")
     * @param null|array $permissions List of user permissions
     * @param bool $condensed (Sendback condensed user data)
     * @return \Illuminate\Support\Collection
     */
    public function listUsers(
        $groupName = NULL,
        $enabledStatus = NULL,
        $createdOnDate = NULL,
        $createdBeforeDate = NULL,
        $createdAfterDate = NULL,
        $permissions = NULL,
        $condensed = FALSE
    ){
        $groupName = $groupName ?? env('AWS_COGNITO_AFFILIATE_USER_GROUP_NAME');
        $enabledStatus = $enabledStatus ?? 'enabled';

        try {
            $users = collect();

            $count = 0;
            while(!isset($result) || $result->hasKey('NextToken') || $result->hasKey('PaginationToken')) {

                if ($groupName !== 'ALL_COGNITO_USERS') {
                    $params = [
                        'GroupName' => $groupName,
                        'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
                    ];

                    if(isset($result)) {
                        $params['NextToken'] = $result->get('NextToken');
                    }

                    $result = $this->getCached('listUsersInGroup', $params);
                }
                else {
                    $params = [
                        'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
                    ];

                    if(isset($result)) {
                        $params['PaginationToken'] = $result->get('PaginationToken');
                    }

                    $result = $this->getCached('listUsers', $params);
                }

                $users = $users->merge(collect($result->get('Users'))
                    ->transform(function($user) use($condensed) {
                        return self::formatUserData($user, $condensed);
                    })
                );

                if($count % $this->listUsersQueryGroupSize === 0) {
                    sleep($this->listUsersSleepTime);
                }

                $count++;
            }

            return $users
                ->filter(function($user) use($enabledStatus) {
                    return ($enabledStatus === 'any'
                        || ($user['user_enabled'] && $enabledStatus === 'enabled')
                        || (!$user['user_enabled'] && $enabledStatus === 'disabled')
                    );
                })
                ->filter(function($user) use($createdOnDate) {
                    $userCreatedDate = date('Y-m-d', strtotime($user['created'])); //strip time from being added to timestamp
                    return (is_null($createdOnDate)
                        || strtotime($userCreatedDate) == strtotime($createdOnDate)
                    );
                })
                ->filter(function($user) use($createdBeforeDate) {
                    $userCreatedDate = date('Y-m-d', strtotime($user['created'])); //strip time from being added to timestamp
                    return (is_null($createdBeforeDate)
                        || strtotime($userCreatedDate) <= strtotime($createdBeforeDate)
                    );
                })
                ->filter(function($user) use($createdAfterDate) {
                    $userCreatedDate = date('Y-m-d', strtotime($user['created'])); //strip time from being added to timestamp
                    return (is_null($createdAfterDate)
                        || strtotime($userCreatedDate) >= strtotime($createdAfterDate)
                    );
                })
                ->filter(function($user) use($permissions){
                    if (is_null($permissions)) {
                        return true;
                    }

                    // Transform user permissions (from Cognito) into array
                    $userPermissions = explode(',',$user['permissions']);

                    // Instead of having to pass all 3 of these permissions ('dashboard-usermanagement', 'dashboard-commissions', 'dashboard-wholesaler'), 'administrator' will act the same.
                    $administratorPermission = in_array('administrator', $permissions);
                    if ($administratorPermission) {
                        $permissions = collect($permissions)
                            ->reject(function($permission){
                                return (
                                    $permission === 'administrator'
                                    || $permission === 'dashboard-usermanagement'
                                    || $permission === 'dashboard-commissions'
                                    || $permission === 'dashboard-wholesaler'
                                );
                            })
                            ->push('dashboard-usermanagement')
                            ->push('dashboard-commissions')
                            ->push('dashboard-wholesaler')
                            ->values()
                            ->all();
                    }

                    return collect($userPermissions)
                        ->filter(function($userPermission) use($permissions){
                            return in_array($userPermission, $permissions);
                        })
                        ->isNotEmpty();
                })
                ->values()
                ->toArray();
        }
        catch(AwsException $e) {
            if($e->getStatusCode() !== 400) { // group not found
                abort(
                    $e->getStatusCode(),
                    $e->getAwsErrorMessage()
                );
            }

            return collect([]);
        }
    }

    public function createUser($username, $password)
    {
        $username = strtolower($username);
        return $this->client->adminCreateUser([
            'TemporaryPassword' => $password,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username,
            'UserAttributes' => [
                [
                    'Name' => 'email_verified',
                    'Value' => 'true',
                ],
                [
                    'Name' => 'email',
                    'Value' => $username
                ]
            ],
        ]);

    }

    /**
     * Delete User from system completely. USE WITH CAUTION!!
     * @param string $username (Cognito User ID)
     * @return \Aws\Result
     */
    public function deleteUser($username)
    {
        return $this->client->adminDeleteUser([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);
    }

    /**
     * Enable User for Cognito AWS. Which will allow them to log into their account.
     * @param string $username (Cognito User ID)
     * @return \Aws\Result
     */
    public function activateUser($username)
    {
        return $this->client->adminEnableUser([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);
    }

    /**
     * Disable User from Cognito AWS. Which will prevent them from logging into their account.
     * @param string $username (Cognito User ID)
     * @return \Aws\Result
     */
    public function deactivateUser($username)
    {
        return $this->client->adminDisableUser([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);
    }

    public function updateUserAttribute($key, $value, $username)
    {
        return $this->client->adminUpdateUserAttributes([
            'UserAttributes' => [
                [
                    'Name' => $key,
                    'Value' => $value,
                ]
            ],
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username,
        ]);
    }

    /**
     * Update Cognito User's email address
     * Note: This method will not properly handle updating user addresses across all platforms. A developer will still have to manually update the email across everything else (Shopify, Wordpress, etc)
     * @param string $value
     * @param string $username (Cognito User ID)
     * @return \Aws\Result
     */
    public function updateUserEmailAddress($value, $username)
    {
        return $this->client->adminUpdateUserAttributes([
            'UserAttributes' => [
                [
                    'Name' => 'email',
                    'Value' => $value,
                ],
                [
                    'Name' => 'email_verified',
                    'Value' => 'true', //this attribute is also required, so that a user will not receive an email notification with a new verification code
                ]
            ],
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username,
        ]);
    }

    /**
     * Force Password Reset on Cognito User
     * @param string $username (Cognito User ID)
     * @return \Aws\Result
     */
    public function resetUserPassword($id)
    {
        return $this->client->adminResetUserPassword([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $id
        ]);
    }

    public function getGroup($groupName)
    {
        return $this->client->getGroup([
            'GroupName' => $groupName,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
        ])->get('Group');
    }

    public function getGroups()
    {
        return $this->client->listGroups([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
        ]);
    }

    public function getGroupsForUser($username)
    {
        $result = $this->client->adminListGroupsForUser([
            'Username' => $username,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
        ]);

        return $result->get('Groups');
    }

    public function createGroup($name, $desc, $precedence = null)
    {
        $params = [
            'Description' => $desc,
            'GroupName' => $name,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
        ];

        if(!is_null($precedence)) {
            $params['Precedence'] = $precedence;
        }

        $result = $this->client->createGroup($params);

        return $result->get('Group');
    }

    public function deleteGroup($name)
    {
        $params = [
            'GroupName' => $name,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
        ];

        $this->client->deleteGroup($params);
    }

    public function addUserToGroup($username, $groupName)
    {
        $this->client->adminAddUserToGroup([
            'GroupName' => $groupName,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);

        return TRUE;
    }

    public function removeUserFromGroup($username, $groupName)
    {
        $this->client->adminRemoveUserFromGroup([
            'GroupName' => $groupName,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);

        return true;
    }

    public function listUsersForGroup($name, $condensed = FALSE)
    {
        $result = $this->client->listUsersInGroup([
            'GroupName' => $name,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
        ]);

        return collect($result->get('Users'))
            ->transform(function($user) use($condensed) {
                return self::formatUserData($user, $condensed);
            });
    }

    public function removeUserAttribute($attributes, $username)
    {
        $result = $this->client->adminDeleteUserAttributes([
            'UserAttributeNames' => $attributes,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);

        return $result->get('Users');
    }

    /**
     * Retrieves data from a given Cognito SDK endpoint. If the data is already cached, the cached data will be
     * returned. If not, the data will be retrieved from Cognito and cached before returning it.
     *
     * @param string $endpoint The SDK endpoint to query.
     * @param array $params An array of parameters to pass to the function.
     * @return mixed
     */
    public function getCached($endpoint, $params)
    {
        $cacheName = $endpoint . $this->cacheTime . md5(serialize($params));
        if(Cache::has($cacheName)) {
            return Cache::get($cacheName);
        }

        $result = $this->client->{$endpoint}($params);
        Cache::put(
            $cacheName,
            $result,
            $this->cacheTime
        );

        return $result;
    }

    /**
     * Sets the helper's cache time to the given value.
     *
     * @param integer $cacheTime The new cacheTime value.
     * @return bool
     */
    public function setCacheTime($cacheTime)
    {
        $this->cacheTime = (int)$cacheTime;
        return TRUE;
    }

    public static function formatUserData($cognitoUser, $condensed = FALSE)
    {
        $attributes = collect($cognitoUser['Attributes']);

        $shopifyId = (int)$attributes
            ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
            ->first()['Value'];

        $permissions = $attributes
            ->where('Name', 'custom:permissions')
            ->first()['Value'];

        $customAttributes = $attributes
            ->where('Name', 'custom:attributes')
            ->first()['Value'];

        if ($condensed === FALSE) {
            $user = new CognitoUser($cognitoUser['Username']);
            $userGroup = $user->group();
            $affiliate = null;
            if (!is_null($userGroup)) {
                $affiliate = $userGroup;
            }
        }

        $userData = [
            'id'                => $cognitoUser['Username'],
            'user_status'       => $cognitoUser['UserStatus'],
            'user_enabled'      => $cognitoUser['Enabled'],
            'email'             => $attributes->where('Name', 'email')->first()['Value'],
            'created'           => $cognitoUser['UserCreateDate'],
            'shopify_id'        => $shopifyId,
            'permissions'       => $permissions,
            'custom_attributes' => $customAttributes
        ];

        if ($condensed === FALSE && isset($affiliate)) {
            $userData['affiliate'] = $affiliate;
        }

        return $userData;
    }
}
