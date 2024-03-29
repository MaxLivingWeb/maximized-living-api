<?php

namespace App;

use App\Events\AddLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\CognitoHelper;
use Aws\Exception\AwsException;

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
        'whitelabel',
        'slug',
        'pre_open_display_date',
        'opening_date',
        'closing_date',
        'daylight_savings_applies',
        'business_hours',
        'timezone_id',
        'deleted_at'
    ];

    protected $dates = ['deleted_at'];
    
    protected $dispatchesEvents = [
        'saving' => AddLocation::class,
        'updated' => AddLocation::class
    ];

    protected $hidden = [
        'updated_id'
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

    public function userGroup() {
        return $this->hasOne('App\UserGroup', 'location_id', 'id');
    }

    public static function filterByRadius($lat, $long, $distance) {

        //default of 50 sounds about right
        if(empty($distance)) {
            $distance = 50;
        }

        $lat = round($lat, 5);
        $long = round($long, 5);

        //TODO: figure out which fields need to be returned in this query
        $query = "SELECT * FROM
            (SELECT ROUND( ( 6371 * acos(
            cos( radians( :lat ) ) *
            cos( radians( latitude ) ) *
            cos( radians( longitude ) -
            radians( :long ) ) +
            sin( radians( :lat2 ) ) *
            sin( radians( latitude ) )
            ) ), 2) as distance,
              address_1,
              address_2,
              latitude,
              longitude,
              zip_postal_code,
              l.name as 'location_name',
              l.id as 'location_id',
              l.telephone as 'location_telephone',
              l.telephone_ext as 'location_telephone_ext',
              l.vanity_website_id as 'location_vanity_website_id',
              l.business_hours as 'location_business_hours',
              l.slug as 'location_slug',
              cities.name as 'city_name',
              cities.slug as 'city_slug',
              regions.name as 'region_name',
              regions.abbreviation as 'region_code',
              countries.name as 'country_name',
              countries.abbreviation as 'country_code',
              ug.id as 'user_group_id',
              ug.premium as 'user_group_premium',
              ug.event_promoter as 'user_group_event_promoter'
            FROM addresses
            INNER JOIN cities
              ON addresses.city_id = cities.id
            INNER JOIN regions
              ON cities.region_id = regions.id
            INNER JOIN countries
              ON regions.country_id = countries.id
            LEFT JOIN locations_addresses la
              ON addresses.id = la.address_id
            JOIN locations l ON la.location_id = l.id
            LEFT JOIN user_groups ug ON ug.location_id = l.id
            WHERE l.deleted_at IS NULL AND
            l.whitelabel = 0) AS query
            WHERE distance <= :distance
            ORDER BY distance ASC";

        $filteredLocations = \DB::select(\DB::raw($query), array(
            'lat' => $lat,
            'lat2' => $lat,
            'long' => $long,
            'distance' => $distance
        ));

        return collect($filteredLocations);
    }

    /**
     * Retrieves a list of all Cognito users associated with a given location.
     * @param null|string $enabledStatus (Get Cognito users by a specific enabled status. 'enabled' (default), 'disabled', 'any'
     * @return array
     */
    public function listUsers($enabledStatus = NULL)
    {
        if (empty($this->userGroup)) {
            return [];
        }

        return $this->userGroup->listUsers($enabledStatus);
    }
}
