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

        $fields['address_2'] = [
            'name' => 'address_2',
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

        $fields['location_business_hours'] = [
            'name' => 'location_business_hours',
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

        $fields['user_group_id'] = [
            'name' => 'user_group_id',
            'type' => Type::int()
        ];

        $fields['user_group_premium'] = [
            'name' => 'user_group_premium',
            'type' => Type::int()
        ];

        $fields['user_group_event_promoter'] = [
            'name' => 'user_group_event_promoter',
            'type' => Type::int()
        ];

        $fields['user_group']  = [
            'args' => [
                'id' => [
                    'type' => Type::int(),
                    'name' => 'id'
                ]
            ],
            'type' => Type::listOf(GraphQL::type('UserGroup')),
            'description' => 'user group',
            'resolve' => function ($root, $args) {
                return  [ $root->userGroup ];
            }
        ];

        return $fields;
    }
}
