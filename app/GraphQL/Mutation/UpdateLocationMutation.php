<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Mutation;
use App\Location;

class UpdateLocationMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateLocation'
    ];

    public function type()
    {
        return GraphQL::type('Location');
    }

    public function args()
    {
        return [
            'id' => ['name' => 'id', 'type' => Type::nonNull(Type::int())],
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
            'timezone_id' => ['name' => 'timezone_id', 'type' => Type::nonNull(Type::int())]
        ];
    }

    public function resolve($root, $args)
    {
        $location = Location::find($args['id']);

        if($location === NULL) {
            return;
        }

        $location_slug = str_replace(' ', '-', strtolower($args['name']) );

        $location->affiliate_id = "456";
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
    }

}