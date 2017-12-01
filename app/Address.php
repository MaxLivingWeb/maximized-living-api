<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'address_1',
        'address_2',
        'city_id'
    ];

    public function city() {
        return $this->belongsTo('App\City');
    }

    public function locations()
    {
        return $this->belongsToMany('App\Location', 'locations_addresses');
    }

    public function types()
    {
        return $this->belongsToMany('App\AddressType', 'locations_addresses');
    }

    public function getType()
    {
        return $this->types->first()->name;
    }

    //takes the location id of the associated location and an array of addresses
    //the address_array is 2-d and each element has an address_1, address_2 and an address type id
    public static function attachAddress($location_id, $city_id, $addresses_array = array() )
    {
        //detach the location from all previous addresses
        $location = Location::find($location_id);
        $location->addresses()->detach();

        foreach($addresses_array as $address) {

            $exists = Address::where([
                ["address_1", $address->address_1 ],
                ["address_2", $address->address_2 ],
                ["city_id", $city_id ],
            ]);

            //if the address exists - attach it to the location
            if($exists->exists() ) {

                $existing_address = Address::find( $exists->first()->id );

                $existing_address->locations()->attach($location_id, ['address_type_id' => $address->type_id]);
                continue;
            }

            //if the address does not exist - create it and attach it to the location
            $new_address = new Address();

            $new_address->address_1 = $address->address_1;
            $new_address->address_2 = $address->address_2;
            $new_address->city_id = $city_id;

            $new_address->save();

            $new_address->locations()->attach($location_id, ['address_type_id' => $address->type_id]);
        }

    }
}
