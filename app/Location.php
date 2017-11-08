<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function addresses()
    {
        return $this->belongsToMany('App\Address', 'locations_addresses');
    }

    public function timezone() {
        return $this->belongsTo('App\Timezone');
    }

    public function getCountry() {
        return $this->getFirstAddress()->city->region->country;
    }

    public function getRegion() {
        return $this->getFirstAddress()->city->region;
    }

    public function getCity() {
        return $this->getFirstAddress()->city;
    }

    public function getFirstAddress() {
        return $this->addresses->first();
    }
}
