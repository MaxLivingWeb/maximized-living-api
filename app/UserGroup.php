<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserGroup extends Model
{
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

    public function addUser($id) {
        DB::table('usergroup_users')->insert(
            [
                'user_group_id' => $this->id,
                'user_id' => $id
            ]
        );
    }

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
                        $shopifyUser = $shopifyUsers
                            ->where('id', $user['shopify_id'])
                            ->first();

                        if(!empty($shopifyUser)) {
                            $user['first_name'] = $shopifyUser->first_name;
                            $user['last_name'] = $shopifyUser->last_name;
                        }

                        return $user;
                    })
                    ->toArray()
            );

            Cache::put($cacheName, json_encode($this->users), 1440);
        }
    }
}
