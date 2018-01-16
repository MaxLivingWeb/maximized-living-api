<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
}
