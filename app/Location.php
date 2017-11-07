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

    public static function filterByRadius($lat, $long, $distance) {

        //TODO: figure out which fields need to be returned in this query
        $filtered_locations = \DB::select("SELECT * FROM 
                                (SELECT ROUND( ( 6371 * acos(
                                cos( radians( $lat ) ) *
                                cos( radians( latitude ) ) *
                                cos( radians( longitude ) -
                                radians( $long ) ) +
                                sin( radians($lat ) ) *
                                sin( radians( latitude ) )
                                ) ), 2) as distance, name, latitude, longitude
                                FROM Locations) AS query 
                                WHERE distance < $distance");

        return $filtered_locations;
    }
}
