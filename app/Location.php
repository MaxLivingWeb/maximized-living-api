<?php

namespace App;

use App\Events\AddLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'affiliate_id',
        'name',
        'zip_postal_code',
        'latitude',
        'longitude',
        'telephone',
        'telephone_ext',
        'fax',
        'email',
        'vanity_website_url',
        'slug',
        'pre_open_display_date',
        'opening_date',
        'closing_date',
        'daylight_savings_applies',
        'operating_hours',
        'timezone_id',
        'deleted_at'
    ];
    protected $dates = ['deleted_at'];
    
    protected $dispatchesEvents = [
        'saving' => AddLocation::class,
        'updated' => AddLocation::class
    ];

    protected $hidden = [
        'updated_id',
        'deleted_at'
    ];

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
