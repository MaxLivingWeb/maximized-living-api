<?php

namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class LocationType extends GraphQLType
{
    protected $attributes = [
        'name' => 'LocationType',
        'description' => 'A type'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the region'
            ],
            'affiliate_id' => [
                'type' => Type::int(),
                'description' => 'affiliate id'
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'Name of the location'
            ],
            'zip_postal_code' => [
                'type' => Type::string(),
                'description' => 'Zip/Postal Code'
            ],
            'latitude' => [
                'type' => Type::float(),
                'description' => 'Latitude of location'
            ],
            'longitude' => [
                'type' => Type::float(),
                'description' => 'Longitude of location'
            ],
            'telephone' => [
                'type' => Type::string(),
                'description' => 'Telephone number of location'
            ],
            'telephone_ext' => [
                'type' => Type::string(),
                'description' => 'Telephone extension number of location'
            ],
            'fax' => [
                'type' => Type::string(),
                'description' => 'Fax number of location'
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'Email of location'
            ],
            'vanity_website_url' => [
                'type' => Type::string(),
                'description' => 'Vanity URL of location'
            ],
            'slug' => [
                'type' => Type::string(),
                'description' => 'slug of location'
            ],
            'pre_open_display_date' => [
                'type' => Type::string(),
                'description' => ''
            ],
            'opening_date' => [
                'type' => Type::string(),
                'description' => 'Opening date of location'
            ],
            'closing_date' => [
                'type' => Type::string(),
                'description' => 'Closing date of location'
            ],
            'daylight_savings_applies' => [
                'type' => Type::boolean(),
                'description' => 'Whether or not daylight savings time applies'
            ],
            'timezone' => [
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'name' => 'id'
                    ]
                ],
                'type' => Type::listOf(GraphQL::type('Timezone')),
                'description' => 'timezone of a location',
                'resolve' => function ($root, $args) {
                    return  [ $root->timezone ] ;
                }
            ],
            'addresses' => [
                'args' => [
                    'id' => [
                        'type' => Type::int(),
                        'name' => 'id'
                    ]
                ],
                'type' => Type::listOf(GraphQL::type('Address')),
                'description' => 'addresses associated with a location',
                'resolve' => function ($root, $args) {
                    return  $root->addresses;
                }
            ],
            'city' => [
                'type' => Type::listOf(GraphQL::type('City')),
                'description' => 'city of the location',
                'resolve' => function ($root, $args) {
                    return  [ $root->getCity() ];
                }
            ],
            'region' => [
                'type' => Type::listOf(GraphQL::type('Region')),
                'description' => 'region of the location',
                'resolve' => function ($root, $args) {
                    return  [ $root->getRegion() ];
                }
            ],
            'country' => [
                'type' => Type::listOf(GraphQL::type('Country')),
                'description' => 'country of the location',
                'resolve' => function ($root, $args) {
                    return  [ $root->getCountry() ];
                }
            ],
        ];
    }
}