<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\City;
use \DB;

class Address extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'address_1',
        'address_2',
        'zip_postal_code',
        'latitude',
        'longitude',
        'city_id'
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
        'city_id',
        'pivot',
        'userGroupTypes',
        'locationTypes',
        'cityRelation',
    ];

    protected $appends = [
        'type', 'city', 'region', 'country'
    ];

    public function getTypeAttribute()
    {
        return $this->getType();
    }

    public function getCityAttribute()
    {
        return $this->cityRelation->name;
    }

    public function getRegionAttribute()
    {
        return $this->cityRelation->region->name;
    }

    public function getCountryAttribute()
    {
        return $this->cityRelation->region->country->name;
    }

    public function cityRelation() {
        return $this->belongsTo('App\City', 'city_id');
    }

    public function locations()
    {
        return $this->belongsToMany('App\Location', 'locations_addresses');
    }

    public function groups()
    {
        return $this->belongsToMany('App\UserGroup', 'usergroup_addresses');
    }

    public function locationTypes()
    {
        return $this->belongsToMany('App\AddressType', 'locations_addresses');
    }

    public function userGroupTypes()
    {
        return $this->belongsToMany('App\AddressType', 'usergroup_addresses');
    }

    public function getType()
    {
        return $this->locationTypes->first() ?? $this->userGroupTypes->first() ?? NULL;
    }

    //takes the location id of the associated location and an array of addresses
    //the address_array is 2-d and each element has an address_1, address_2 and an address type id
    static public function attachAddress($location_id, $address)
    {
        $cityId = City::checkCity( $address['country'], $address['region'], $address['city'] );
        
        if(isset($address['id'])) {

            $existing_address = Address::where('id', $address['id']);

            $existing_address->locations()->attach($location_id, ['address_type_id' => $address['address_type']]);
            return;
        }

        //if the address does not exist - create it and attach it to the location
        $new_address = new Address();

        $new_address->address_1 = $address['address_1'];
        $new_address->address_2 = $address['address_2'];
        $new_address->zip_postal_code = $address['zip_postal_code'];
        $new_address->latitude = $address['latitude'];
        $new_address->longitude = $address['longitude'];
        $new_address->city_id = $cityId;

        $new_address->save();
        
        $new_address->locations()->attach($location_id, ['address_type_id' => $address['address_type']]);
    }
}
