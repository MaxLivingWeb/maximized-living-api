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
    
    public static function checkCountry($countryName) {
        
        $country = Country::where([
            ["name", $countryName ]
        ]);
        
        if($country->exists() ) {
            //return the existing city id if the city exists
            return $country->first()->id;
        }
        
        //create the new city and return the new id
        $newCountry = new Country();
        
        $newCountry->name = $countryName;
        
        $newCountry->save();
        
        return $newCountry->id;
    }
}
