<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $hidden = [
        'updated_at',
        'deleted_at',
        'region_id'
    ];

    public function regions() {
        return $this->hasMany('App\Region');
    }
}
