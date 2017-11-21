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

    public function listUsers()
    {
        return $this->client->listUsers([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
        ]);
    }

    public function createUser($username, $password)
    {
        return $this->client->adminCreateUser([
            'TemporaryPassword' => $password,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
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

    public function getUserGroups()
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

    public function getGroupsForUser($username)
    {
        $result = $this->client->adminListGroupsForUser([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);

        return collect($result->get('Groups'))->sortByDesc('Precedence');
    }

    public function addUserToGroup($username, $groupName)
    {
        $this->client->adminAddUserToGroup([
            'GroupName' => $groupName,
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'),
            'Username' => $username
        ]);

        return true;
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

        return $result->get('Users');
    }
}
