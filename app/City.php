<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $hidden = [
        'updated_at',
        'deleted_at',
        'region_id'
    ];

    public function region() {
        return $this->belongsTo('App\Region');
    }

    public function market() {
        return $this->belongsTo('App\Market');
    }

    public function addresses() {
        return $this->hasMany('App\Address');
    }

    //  takes a city name, creates a new city if it doesn't exists and returns the new ID.
    //  if the city exists it returns the ID
    public static function checkCity($country, $region, $city) {
    
        $regionId = Region::checkRegion($country, $region);
        $cityId = City::where([
            ["name", $city ],
            ["region_id", $regionId ]
        ]);

        if($cityId->exists() ) {
            //return the existing city id if the city exists
            return $cityId->first()->id;
        }

        //create the new city and return the new id
        $new_city = new City();

        $new_city->name = $city;
        $new_city->region_id = $regionId;
        $new_city->slug = str_slug($city);

        $new_city->save();

        return $new_city->id;
    }
}
