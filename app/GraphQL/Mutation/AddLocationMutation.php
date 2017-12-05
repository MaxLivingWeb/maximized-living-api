<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\GraphQL\Type\LocationType;

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
        $locationType = new LocationType();
        return $locationType->fields();
    }
    
    public function resolve($root, $args)
    {
        /*
         * query format example
         * http://max-living-locations-2.dev/graphql?query=mutation+mutation{addLocation(addresses:%22[{\%22address_1\%22:\%22richmond%20st\%22,\%22address_2\%22:\%22unit%203\%22,\%22type_id\%22:1},{\%22address_1\%22:\%22oxford%20st\%22,\%22address_2\%22:\%22unit%209\%22,\%22type_id\%22:2}]%22,region_id:1,city:%22Londy%22,daylight_savings_applies:false,pre_open_display_date:%2202-02-02%22,opening_date:%2202-02-02%22,closing_date:%2208-06-04%22,name:%22tommylandPART2%22,zip_postal_code:%2290210%22,latitude:45.8543456,longitude:-91.1234564,telephone:%22519-472-1718%22,telephone_ext:%2298%22,fax:%2212345%22,email:%22tom@tom.com%22,vanity_website_url:%22vanity_url%22,timezone_id:1){name,latitude}}
        */

        //create the city if it's not in the system and return id, or get the id of the existing city
        $city_id  = City::checkCity($args['city'], $args['region_id'] );

        $location_slug = str_replace(' ', '-', strtolower($args['name']) );

        $location = new Location();

        //TODO figure out what is needed of an affiliate id and operating hours (hardcoded a JSON string for now)
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

        //takes all the addresses snd creates/updates as needed and attaches them to the location
        Address::attachAddress($location->id, $city_id, $addresses);
    }
}
