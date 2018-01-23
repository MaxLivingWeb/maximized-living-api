<?php

namespace App\Helpers;

use Aws\Sdk;

class CognitoHelper
{
    private $client;

    function __construct()
    {
        $sharedConfig = [
            'region'  => 'us-east-2',
            'version' => '2016-04-18'
        ];

        $sdk = new Sdk($sharedConfig);

        $this->client = $sdk->createCognitoIdentityProvider();
    }

    public function getUser($id)
    {
        return $this->client->adminGetUser([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $id
        ]);
    }

    /**
     * Returns an array of users from Cognito.
     *
     * @param string|null $groupName The name of the group to get users for. If no group name is provided, will default to the .env affiliate group name.
     *
     * @return \Illuminate\Support\Collection
     */
    public function listUsers($groupName = NULL)
    {
        if(!$groupName){
            $groupName = env('AWS_COGNITO_AFFILIATE_USER_GROUP_NAME');
        }

        $result = $this->client->listUsersInGroup([
            'GroupName' => $groupName,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
        ]);

        $users = collect($result->get('Users'))->transform(function($user) {
            return self::formatUserData($user);
        });

        return $users;
    }

    public function createUser($username, $password)
    {
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

    public function getGroups()
    {
        return $this->client->listGroups([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
        ]);
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

    public function removeUserFromGroup($username, $groupName)
    {
        $this->client->adminRemoveUserFromGroup([
            'GroupName' => $groupName,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);

        return true;
    }

    public function listUsersForGroup($name)
    {
        $result = $this->client->listUsersInGroup([
            'GroupName' => $name,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
        ]);

        return collect($result->get('Users'))->transform(function($user) {
            return self::formatUserData($user);
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

    public static function formatUserData($user)
    {
        $attributes = collect($user['Attributes']);
        return [
            'id'            => $user['Username'],
            'user_status'    => $user['UserStatus'],
            'email'         => $attributes->where('Name', 'email')->first()['Value'],
            'created'       => $user['UserCreateDate'],
            'shopify_id'     => intval($attributes->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))->first()['Value'])
        ];
    }
}
