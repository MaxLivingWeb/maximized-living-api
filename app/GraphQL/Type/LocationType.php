<?php

namespace App\GraphQL\Type;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Type as GraphQLType;

class LocationType extends GraphQLType
{
    //protected $inputObject = true;

    protected $attributes = [
        'name' => 'Location',
        'description' => 'A type'
    ];

    public function fields()
    {
        return [
            'id' => [
                'type' => Type::int(),
                'description' => 'ID of the region'
            ],
            'name' => [
                'type' => Type::string(),
                'description' => 'Name of the location'
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
            'vanity_website_id' => [
                'type' => Type::int(),
                'description' => 'Vanity ID of the location'
            ],
            'whitelabel' => [
                'type' => Type::int(),
                'description' => 'Flag if the site is whitelabel status'
            ],
            'sem_page_desc' => [
                'type' => Type::string(),
                'description' => 'sem page desc'
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
                'type' => Type::int(),
                'description' => 'Whether or not daylight savings time applies'
            ],
            'timezone_id' => [
                'type' => Type::int(),
                'description' => 'Id of the timezone'
            ],
            'addresses' => [
                'name' => 'addresses',
                'type' => Type::listOf(GraphQL::type('Address'))
            ],
            'business_hours' => [
                'name' => 'business_hours',
                'type' => Type::string()
            ],
            'gmb_id' => [
                'name' => 'gmb_id',
                'type' => Type::string()
            ]
        ];
    }
}
