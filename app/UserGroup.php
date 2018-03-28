<?php

namespace App;

use App\Helpers\CognitoHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\SoftDeletes;


class UserGroup extends Model
{
	use SoftDeletes;

	protected $dates = ['deleted_at'];

    protected $fillable = [
        'group_name',
        'group_name_display',
        'wholesaler',
        'legacy_affiliate_id',
        'commission_id',
        'location_id',
        'premium',
        'event_promoter',
        'maxtv_token'
    ];

    protected $hidden = [
        'commission_id',
        'location_id',
        'created_at',
        'updated_id',
        'deleted_at'
    ];

    protected $casts = [
        'wholesaler'     => 'boolean',
        'premium'        => 'boolean',
        'event_promoter' => 'boolean'
    ];

    protected $appends = [
        'users'
    ];

    protected $users = [];

    public function getUsersAttribute()
    {
        return $this->users;
    }
    
    public function commission() {
        return $this->hasOne('App\CommissionGroup', 'id', 'commission_id');
    }

    public function location() {
        return $this->hasOne('App\Location', 'id', 'location_id');
    }

    /**
     * Associate User to this UserGroup
     * @param $id Cognito User ID
     * @return void
     */
    public function addUser($id) {
        DB::table('usergroup_users')->insert(
            [
                'user_group_id' => $this->id,
                'user_id' => $id
            ]
        );
    }

    /**
     * Disassociate User ID from this UserGroup ID (hard delete record)
     * @param string $id Cognito User ID
     * @return void
     */
    public function deleteUser($id) {
        DB::table('usergroup_users')
            ->where([
                'user_group_id' => $this->id,
                'user_id' => $id
            ])
            ->delete();

        // Check to see if this was a single-user group, and if so, delete the group as well since no users are apart of this anymore
        if (strpos($this->group_name, 'user.') !== false) {
            // Delete relations
            DB::table('usergroup_addresses')->where('user_group_id', $this->id)->delete();
            // Delete this UserGroup
            $this->delete();
        }
    }

    public function addresses()
    {
        return $this->belongsToMany('App\Address', 'usergroup_addresses');
    }

    /**
     * Assign (protected) $users variable to this UserGroup
     * @param null|string $enabledStatus (Get Cognito users by a specific enabled status. 'enabled' (default), 'disabled', 'any'
     * @return void
     */
    public function assignUsersToUserGroup($enabledStatus = NULL)
    {
        $this->users = $this->listUsers($enabledStatus);
    }

    /**
     * List Users for this UserGroup, by checking all the associated `user_id`'s mapped to this `user_group_id`. NOTE: This method does not use caching.
     * @param null|string $enabledStatus (Get Cognito users by a specific enabled status. 'enabled' (default), 'disabled', 'any'
     * @return array|null
     */
    public function listUsers($enabledStatus = NULL)
    {
        $cognito = new CognitoHelper();

        $enabledStatus = $enabledStatus ?? 'enabled';

        $userIds = DB::table('usergroup_users')
            ->where('user_group_id', '=', $this->id)
            ->get()
            ->pluck('user_id')
            ->unique();

        return collect($userIds)
            ->transform(function($userId) use($cognito){
                $cognitoUser = $cognito->getUser($userId);

                $attributes = $cognitoUser['UserAttributes']
                    ?? $cognitoUser['Attributes']
                    ?? [];

                $customAttributes = collect($attributes)
                    ->where('Name', 'custom:attributes')
                    ->first()['Value'];
                $customAttributes = explode(',', $customAttributes);
                if (!empty($customAttributes) && in_array('hide-from-affiliate-group', $customAttributes, true)) {
                    return; // Skip this user since it should be hidden from the affiliate group. Most likely an Admin who was secretly added to an Affiliate group to mimic specific page displays (Content Portal, Ecomm, etc).
                }

                if (!empty($cognitoUser)) {
                    return User::structureUser($cognitoUser);
                }
            })
            ->reject(function($user){
                return is_null($user);
            })
            ->filter(function($user) use($enabledStatus) {
                if ($enabledStatus === 'any'
                    || ($user->user_enabled && $enabledStatus === 'enabled')
                    || (!$user->user_enabled && $enabledStatus === 'disabled')
                ) {
                    return true;
                }
                return false;
            })
            ->values()
            ->all();
    }

    /**
     * Load Users into protected $users variable, by caching results
     * @param array $allUsers
     * @param array $shopifyUsers
     * @return void
     */
    public function loadUsers($allUsers, $shopifyUsers) {
        // this logic seems to take a long time to run, so we'll cache it as well
        $cacheName = 'location_' . $this->id . '_all_users';
        if(Cache::has($cacheName)) {
            $this->users = json_decode(Cache::get($cacheName), TRUE);
        } else {
            $userIds = DB::table('usergroup_users')
                ->where('user_group_id', '=', $this->id)
                ->get()
                ->pluck('user_id')
                ->unique();

            $shopifyUsers = collect($shopifyUsers);

            $this->users = array_values(
                collect($allUsers)
                    ->whereIn('id', $userIds)
                    ->transform(function($user) use ($shopifyUsers){
                        if (isset($user['custom_attributes'])) {
                            $customAttributes = explode(',', $user['custom_attributes']);
                            if (!empty($customAttributes) && in_array('hide-from-affiliate-group', $customAttributes, true)) {
                                return; // Skip this user since it should be hidden from the affiliate group. Most likely an Admin who was secretly added to an Affiliate group to mimic specific page displays (Content Portal, Ecomm, etc).
                            }
                        }

                        $shopifyUser = $shopifyUsers
                            ->where('id', $user['shopify_id'])
                            ->first();

                        if(!empty($shopifyUser)) {
                            $user['first_name'] = $shopifyUser->first_name;
                            $user['last_name'] = $shopifyUser->last_name;
                        }

                        return $user;
                    })
                    ->reject(function($user){
                        return is_null($user);
                    })
                    ->toArray()
            );

            Cache::put($cacheName, json_encode($this->users), 1440);
        }
    }

    /**
     * Creates a new 'single user' UserGroup for the given user.
     *
     * @param array $data An array containing the data for the usergroup.
     * @param string $id The ID of the user to create a usergroup for.
     * @return \App\UserGroup
     */
    public static function createGroupForUser(array $user, string $id)
    {
        // add the user to their own user group if they don't have one
        $params = [
            'group_name' => 'user.' . $user['email'],
            'group_name_display' => $user['first_name'].' '.$user['last_name']
        ];

        if(isset($user['legacyId'])) {
            $params['legacy_affiliate_id'] = $user['legacyId'];
        }

        if(isset($user['commission']['id'])) {
            $params['commission_id'] = $user['commission']['id'];
        }

        if(isset($user['wholesaler'])) {
            $params['wholesaler'] = $user['wholesaler'];
        }

        $userGroup = UserGroup::create($params);
        $userGroup->addUser($id);

        return $userGroup;
    }
}
