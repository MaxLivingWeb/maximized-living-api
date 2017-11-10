<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function region() {
        return $this->belongsTo('App\Region');
    }

    public function addresses() {
        return $this->hasMany('App\Address');
    }
}
