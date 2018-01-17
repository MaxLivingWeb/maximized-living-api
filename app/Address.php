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
    ];

    protected $appends = [
        'type', 'city', 'region', 'country'
    ];

    /**
     * Returns and appends the 'type' relation of the address (location type first, falling back to usergroup type).
     *
     * @return mixed
     */
    public function getTypeAttribute()
    {
        return $this->getType();
    }

    /**
     * Returns and appends the city name for the address.
     *
     * @return string
     */
    public function getCityAttribute()
    {
        return $this->cityRelation->name;
    }

    /**
     * Returns and appends the region name for the address.
     *
     * @return string
     */
    public function getRegionAttribute()
    {
        return $this->cityRelation->region->name;
    }

    /**
     * Returns and appends the country name for the address.
     *
     * @return string
     */
    public function getCountryAttribute()
    {
        return $this->cityRelation->region->country->name;
    }

    /**
     * Returns the relation to the City model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cityRelation() {
        return $this->belongsTo('App\City', 'city_id');
    }

    /**
     * Returns the relation to the Location model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function locations()
    {
        return $this->belongsToMany('App\Location', 'locations_addresses');
    }

    /**
     * Returns the relation to the UserGroup model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany('App\UserGroup', 'usergroup_addresses');
    }

    /**
     * Returns the relation to the AddressType model through the locations_addresses table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function locationTypes()
    {
        return $this->belongsToMany('App\AddressType', 'locations_addresses');
    }

    /**
     * Returns the relation to the AddressType model through the usergroup_addresses table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function userGroupTypes()
    {
        return $this->belongsToMany('App\AddressType', 'usergroup_addresses');
    }

    /**
     * Returns the 'type' relation of the address (location type first, falling back to usergroup type).
     *
     * @return \App\AddressType|null
     */
    public function getType()
    {
        return $this->locationTypes->first() ?? $this->userGroupTypes->first() ?? NULL;
    }

    /**
     * Attaches an address to a given location.
     *
     * @param integer $location_id The location id of the associated location
     * @param array $address 2D array containing address_1, address_2, and an address type ID.
     */
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
