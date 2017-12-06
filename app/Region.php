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
    
    public static function checkRegion($country, $regionName) {
    
        $countryId = Country::checkCountry($country);
        
        $regionId = Region::where([
            ["name", $regionName],
            ["country_id", $countryId ]
        ]);
        
        if($regionId->exists() ) {
            //return the existing city id if the city exists
            return $regionId->first()->id;
        }
        
        //create the new city and return the new id
        $newRegion = new Region();
        
        $newRegion->name = $regionName;
        $newRegion->country_id = $countryId;
        
        $newRegion->save();
        
        return $newRegion->id;
    }
}
