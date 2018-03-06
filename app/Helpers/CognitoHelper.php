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
     * @param string|null $groupName The name of the group to get users for. If no group name is provided, will default to the .env affiliate group name. To return all users - pass the value 'ALL_COGNITO_USERS'
     * @return \Illuminate\Support\Collection
     */
    public function listUsers($groupName = NULL, $condensed = FALSE)
    {
        if (!$groupName) {
            $groupName = env('AWS_COGNITO_AFFILIATE_USER_GROUP_NAME');
        }

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

            return $users->toArray();
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

    public function deleteUser($username)
    {
        return $this->client->adminDeleteUser([
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

        if ($condensed === FALSE) {
            $user = new CognitoUser($cognitoUser['Username']);
            $userGroup = $user->group();
            $affiliate = null;
            if (!is_null($userGroup)) {
                $affiliate = $userGroup;
            }
        }

        $userData = [
            'id'           => $cognitoUser['Username'],
            'user_status'  => $cognitoUser['UserStatus'],
            'email'        => $attributes->where('Name', 'email')->first()['Value'],
            'created'      => $cognitoUser['UserCreateDate'],
            'shopify_id'   => $shopifyId
        ];

        if ($condensed === FALSE && isset($affiliate)) {
            $userData['affiliate'] = $affiliate;
        }

        return $userData;
    }
}
