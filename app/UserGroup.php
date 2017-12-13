<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserGroup extends Model
{
    protected $fillable = [
        'group_name',
        'group_name_display',
        'discount_id',
        'legacy_affiliate_id',
        'commission_id',
        'location_id',
        'premium',
        'event_promoter'
    ];

    protected $appends = [
        'collections'
    ];

    protected $hidden = [
        'commission_id',
        'location_id',
        'created_at',
        'updated_id',
        'deleted_at'
    ];

    protected $casts = [
        'premium'        => 'boolean',
        'event_promoter' => 'boolean'
    ];

    public function getCollectionsAttribute()
    {
        return DB::table('usergroup_collections')->where('usergroup_id', $this->id)->pluck('collection_id');
    }

    public function commission() {
        return $this->hasOne('App\CommissionGroup', 'id', 'commission_id');
    }

    public function location() {
        return $this->hasOne('App\Location', 'id', 'location_id');
    }
}
