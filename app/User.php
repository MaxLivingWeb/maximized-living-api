<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public static function structureUser($cognitoUser)
    {
        if (empty($cognitoUser)) {
            return;
        }

        //depending on the endpoint used to get user data, this may differentiate (adminGetUser vs listUsers)
        $attributes = $cognitoUser['UserAttributes']
            ?? $cognitoUser['Attributes']
            ?? [];

        $res = (object) [
            'id'    => $cognitoUser['Username'],
            'email' => collect($attributes)
                ->where('Name', 'email')
                ->first()['Value'],
            'user_status' => $cognitoUser['UserStatus'],
            'user_enabled' => $cognitoUser['Enabled'],
            'created' => $cognitoUser['UserCreateDate']
        ];

        $permissions = collect($attributes)->where('Name', 'custom:permissions')->first();
        if(!is_null($permissions)) {
            $res->permissions = explode(',', $permissions['Value']);
        }

        $customAttributes = collect($attributes)->where('Name', 'custom:attributes')->first();
        if(!is_null($customAttributes)) {
            $res->custom_attributes = explode(',', $customAttributes['Value']);
        }

        $res->addresses = $userGroup->location->addresses
            ?? $userGroup->addresses
            ?? [];

        return $res;
    }
}
