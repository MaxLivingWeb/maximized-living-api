<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function city() {
        return $this->belongsTo('App\City');
    }

    public function locations()
    {
        return $this->belongsToMany('App\Location', 'locations_addresses');
    }

    public function types()
    {
        return $this->belongsToMany('App\AddressType', 'locations_addresses');
    }

    public function getType()
    {
        return $this->types->first()->name;
    }
}
