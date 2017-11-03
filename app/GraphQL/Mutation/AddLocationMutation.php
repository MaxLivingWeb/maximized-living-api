<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\Address;
use App\City;

class AddLocationMutation extends Mutation
{
    protected $attributes = [
        'name' => 'addLocation'
    ];

    public function type()
    {
        return GraphQL::type('Location');
    }

    public function args()
    {
        return [
            'name' => ['name' => 'name', 'type' => Type::nonNull(Type::string())],
            'zip_postal_code' => ['name' => 'zip_postal_code', 'type' => Type::nonNull(Type::string())],
            'latitude' => ['name' => 'latitude', 'type' => Type::nonNull(Type::float())],
            'longitude' => ['name' => 'longitude', 'type' => Type::nonNull(Type::float())],
            'telephone' => ['name' => 'telephone', 'type' => Type::nonNull(Type::string())],
            'telephone_ext' => ['name' => 'telephone_ext', 'type' => Type::string()],
            'fax' => ['name' => 'fax', 'type' => Type::string()],
            'email' => ['name' => 'email', 'type' => Type::nonNull(Type::string())],
            'vanity_website_url' => ['name' => 'vanity_website_url', 'type' => Type::string()],
            'pre_open_display_date' => ['name' => 'pre_open_display_date', 'type' => Type::string()],
            'opening_date' => ['name' => 'opening_date', 'type' => Type::string()],
            'closing_date' => ['name' => 'closing_date', 'type' => Type::string()],
            'daylight_savings_applies' => ['name' => 'daylight_savings_applies', 'type' => Type::boolean()],

            'timezone_id' => ['name' => 'timezone_id', 'type' => Type::nonNull(Type::int())],

            'city' => ['name' => 'city', 'type' => Type::nonNull(Type::string())],

            'region_id' => ['name' => 'region_id', 'type' => Type::nonNull(Type::int())],
            //setting as a string type and will send address info as json string
            'addresses' => ['name' => 'addresses', 'type' => Type::nonNull(Type::string())],
        ];
    }

    public function resolve($root, $args)
    {

        $location_slug = str_replace(' ', '-', strtolower($args['name']) );

        //check if city exists based on the string based through
        $city = City::where([
            ["name", $args['city'] ],
            ["region_id", $args['region_id'] ]
        ]);

        if($city->exists() ) {
            //get the city_id if it exists
            $city_id = $city->first()->id;
        } else {
            //end point for adding a new location and returning it's id
            $new_city = new City();

            $new_city->name = $args['city'];
            $new_city->region_id = $args['region_id'];

            $new_city->save();

            $city_id =  $new_city->id;
        }

        //add the location
        $location = new Location();

        $location->affiliate_id = "123";
        $location->name = $args['name'];
        $location->zip_postal_code = $args['zip_postal_code'];
        $location->latitude = $args['latitude'];
        $location->longitude = $args['longitude'];
        $location->telephone = $args['telephone'];
        $location->telephone_ext = $args['telephone_ext'];
        $location->fax = $args['fax'];
        $location->email = $args['email'];
        $location->vanity_website_url = $args['vanity_website_url'];
        $location->slug = $location_slug;
        $location->pre_open_display_date = $args['pre_open_display_date'];
        $location->opening_date = $args['opening_date'];
        $location->closing_date = $args['closing_date'];
        $location->daylight_savings_applies = $args['daylight_savings_applies'];
        $location->operating_hours = "\"businessHours\": {
            \"periods\": [
            {
              \"openDay\": \"MONDAY\",
              \"closeDay\": \"MONDAY\",
              \"openTime\": \"09:00\",
              \"closeTime\": \"17:00\"
            },
            {
              \"openDay\": \"TUESDAY\",
              \"closeDay\": \"TUESDAY\",
              \"openTime\": \"09:00\",
              \"closeTime\": \"17:00\"
            },
            {
              \"openDay\": \"TUESDAY\",
              \"closeDay\": \"TUESDAY\",
              \"openTime\": \"09:00\",
              \"closeTime\": \"17:00\"
            },
            {
              \"openDay\": \"WEDNESDAY\",
              \"closeDay\": \"WEDNESDAY\",
              \"openTime\": \"09:00\",
              \"closeTime\": \"17:00\"
            },
            {
              \"openDay\": \"THURSDAY\",
              \"closeDay\": \"THURSDAY\",
              \"openTime\": \"09:00\",
              \"closeTime\": \"17:00\"
            },
            {
              \"openDay\": \"FRIDAY\",
              \"closeDay\": \"FRIDAY\",
              \"openTime\": \"09:00\",
              \"closeTime\": \"17:00\"
            },
          ]
        },}"; //hardcoding for now - need widget
        $location->timezone_id = $args['timezone_id'];

        $location->save();

        //now add the address and associate it with the city

        //decode the address and iterate through them and add them
        $addresses = json_decode($args['addresses']);

        foreach($addresses as $address) {
            $new_address = new Address();

            $new_address->address_1 = $address->address_1;
            $new_address->address_2 = $address->address_2;
            $new_address->city_id = $city_id;

            $new_address->save();

            $new_address->locations()->attach($location->id, ['address_type_id' => $address->type_id]);
        }

        dd("hi");
    }

}