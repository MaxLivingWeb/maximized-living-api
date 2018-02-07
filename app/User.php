<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Helpers\ShopifyHelper;

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
        $res = (object) [
            'id'    => $cognitoUser->get('Username'),
            'email' => collect($cognitoUser['UserAttributes'])
                ->where('Name', 'email')
                ->first()['Value'],
            'user_status' => $cognitoUser->get('UserStatus'),
            'created' => $cognitoUser->get('UserCreateDate')
        ];

        $shopifyId = collect($cognitoUser['UserAttributes'])
            ->where('Name', env('COGNITO_SHOPIFY_CUSTOM_ATTRIBUTE'))
            ->first()['Value'];

        $affiliateId = collect($cognitoUser['UserAttributes'])
            ->where('Name', 'custom:affiliateId')
            ->first()['Value'];

        $shopify = new ShopifyHelper();
        $shopifyCustomer = $shopify->getCustomer($shopifyId);

        $shopifyCustomerCompanyName = $shopifyCustomer->default_address->company;

        $res->shopify_id = $shopifyCustomer->id;
        $res->referred_affiliate_id = is_null($affiliateId) ? $affiliateId : intval($affiliateId);
        $res->first_name = $shopifyCustomer->first_name;
        $res->last_name = $shopifyCustomer->last_name;
        $res->phone = $shopifyCustomer->phone;
        $res->business = (object)['name' => $shopifyCustomerCompanyName];

        $user = new CognitoUser($cognitoUser->get('Username'));
        $userGroup = $user->group();
        if(!is_null($userGroup)) {
            $res->affiliate = $userGroup;
        }

        $permissions = collect($cognitoUser['UserAttributes'])->where('Name', 'custom:permissions')->first();
        if(!is_null($permissions)) {
            $res->permissions = explode(',', $permissions['Value']);
        }

        $res->addresses = $userGroup->location->addresses
            ?? $userGroup->addresses
            ?? [];

        return $res;
    }
}
