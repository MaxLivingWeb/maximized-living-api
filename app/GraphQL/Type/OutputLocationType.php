<?php

namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class OutputLocationType extends LocationType
{
    protected $inputObject = false;

    protected $attributes = [
        'name' => 'OutputLocation',
        'description' => 'A type location type for outputting'
    ];

    public function fields() {

        $fields = parent::fields();

        $fields['addresses']  = [
            'name' => 'addresses',
            'type' => Type::listOf(GraphQL::type('OutputAddress'))
        ];

        $fields['address_1'] = [
            'name' => 'address_1',
            'type' => Type::string()
        ];

        $fields['latitude'] = [
            'name' => 'latitude',
            'type' => Type::float()
        ];

        $fields['longitude'] = [
            'name' => 'longitude',
            'type' => Type::float()
        ];

        $fields['zip_postal_code'] = [
            'name' => 'zip_postal_code',
            'type' => Type::string()
        ];

        $fields['location_name'] = [
            'name' => 'location_name',
            'type' => Type::string()
        ];

        $fields['location_id'] = [
            'name' => 'location_id',
            'type' => Type::int()
        ];

        $fields['location_slug'] = [
            'name' => 'location_slug',
            'type' => Type::string()
        ];

        $fields['location_telephone'] = [
            'name' => 'location_telephone',
            'type' => Type::string()
        ];

        $fields['location_telephone_ext'] = [
            'name' => 'location_telephone_ext',
            'type' => Type::string()
        ];

        $fields['location_vanity_website_id'] = [
            'name' => 'location_vanity_website_id',
            'type' => Type::int()
        ];

        $fields['location_operating_hours'] = [
            'name' => 'location_operating_hours',
            'type' => Type::string()
        ];

        $fields['city_name'] = [
            'name' => 'city_name',
            'type' => Type::string()
        ];

        $fields['city_slug'] = [
            'name' => 'city_slug',
            'type' => Type::string()
        ];

        $fields['region_name'] = [
            'name' => 'region_name',
            'type' => Type::string()
        ];

        $fields['region_code'] = [
            'name' => 'region_code',
            'type' => Type::string()
        ];

        $fields['country_code'] = [
            'name' => 'country_code',
            'type' => Type::string()
        ];

        $fields['country_name'] = [
            'name' => 'country_name',
            'type' => Type::string()
        ];

        return $fields;
    }
}
