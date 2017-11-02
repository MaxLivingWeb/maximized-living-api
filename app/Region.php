<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function cities() {
        return $this->hasMany('App\City');
    }

    public function country() {
        return $this->belongsTo('App\Country');
    }
}
