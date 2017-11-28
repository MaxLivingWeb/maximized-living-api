<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $fillable = [
        'group_name',
        'discount_id',
        'legacy_affiliate_id',
        'commission_id'
    ];

    public function commission() {
        return $this->hasOne('App\CommissionGroup', 'id', 'commission_id');
    }
}
