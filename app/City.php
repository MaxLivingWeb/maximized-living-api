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

    //  takes a city name, creates a new city if it doesn't exists and returns the new ID.
    //  if the city exists it returns the ID
    public static function checkCity($city_name, $region_id) {

        $city = City::where([
            ["name", $city_name ],
            ["region_id", $region_id ]
        ]);

        if($city->exists() ) {
            //return the existing city id if the city exists
            return $city->first()->id;
        }

        //create the new city and return the new id
        $new_city = new City();

        $new_city->name = $city_name;
        $new_city->region_id = $region_id;

        $new_city->save();

        return $new_city->id;
    }
}
