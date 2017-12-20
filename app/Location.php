<?php

namespace App;

use App\Events\AddLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'telephone',
        'telephone_ext',
        'fax',
        'email',
        'vanity_website_url',
        'vanity_website_id',
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

        //default of 50 sounds about right
        if(empty($distance)) {
            $distance = 50;
        }

        $lat = round($lat, 5);
        $long = round($long, 5);

        //TODO: figure out which fields need to be returned in this query
        $filteredLocations = \DB::select("SELECT * FROM
                                (SELECT ROUND( ( 6371 * acos(
                                cos( radians( $lat ) ) *
                                cos( radians( latitude ) ) *
                                cos( radians( longitude ) -
                                radians( $long ) ) +
                                sin( radians($lat ) ) *
                                sin( radians( latitude ) )
                                ) ), 2) as distance,
                                  address_1,
                                  latitude,
                                  longitude,
                                  zip_postal_code,
                                  l.name as 'location_name',
                                  l.id as 'location_id',
                                  l.telephone as 'location_telephone',
                                  l.telephone_ext as 'location_telephone_ext',
                                  l.vanity_website_id as 'location_vanity_website_id',
                                  l.operating_hours as 'location_operating_hours',
                                  cities.name as 'city_name',
                                  cities.slug as 'city_slug',
                                  regions.name as 'region_name',
                                  regions.abbreviation as 'region_code',
                                  countries.name as 'country_name',
                                  countries.abbreviation as 'country_code'
                                FROM Addresses
                                INNER JOIN cities
                                  ON addresses.city_id = cities.id
                                INNER JOIN regions
                                  ON cities.region_id = regions.id
                                INNER JOIN countries
                                  ON regions.country_id = countries.id
                                LEFT JOIN locations_addresses la
                                  ON addresses.id = la.address_id
                                  JOIN locations l ON la.location_id = l.id) AS query
                                WHERE distance <= $distance");

        return collect($filteredLocations);
    }
}
