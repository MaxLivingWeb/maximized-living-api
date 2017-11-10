<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Folklore\GraphQL\Support\Mutation;
use App\Location;

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
        //these are the parameters that are need - some can be null - but the parameter itself needs to be in the mutation
        return [
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string())
            ],
            'zip_postal_code' => [
                'name' => 'zip_postal_code',
                'type' => Type::nonNull(Type::string())
            ],
            'latitude' => [
                'name' => 'latitude',
                'type' => Type::nonNull(Type::float())
            ],
            'longitude' => [
                'name' => 'longitude',
                'type' => Type::nonNull(Type::float())
            ],
            'telephone' => [
                'name' => 'telephone',
                'type' => Type::nonNull(Type::string())
            ],
            'telephone_ext' => [
                'name' => 'telephone_ext',
                'type' => Type::string()],
            'fax' => [
                'name' => 'fax',
                'type' => Type::string()
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::nonNull(Type::string())
            ],
            'vanity_website_url' => [
                'name' => 'vanity_website_url',
                'type' => Type::string()
            ],
            'pre_open_display_date' => [
                'name' => 'pre_open_display_date',
                'type' => Type::string()
            ],
            'opening_date' => [
                'name' => 'opening_date',
                'type' => Type::string()
            ],
            'closing_date' => [
                'name' => 'closing_date',
                'type' => Type::string()
            ],
            'daylight_savings_applies' => [
                'name' => 'daylight_savings_applies',
                'type' => Type::int()
            ],
            'timezone_id' => [
                'name' => 'timezone_id',
                'type' => Type::nonNull(Type::int())
            ]
        ];
    }
    
    public function resolve($root, $args)
    {
        $location_slug = str_slug($args['name']);
        
        foreach ($args as $key => $var) {
            $args[$key] = filter_var($var, FILTER_SANITIZE_STRING);
        }
        
        $business_hours = [
            'businessHours' => [
                'periods' => [
                    'openDay' => 'MONDAY',
                    'closeDay' => 'MONDAY',
                    'openTime' => '09:00',
                    'closeTime' => '17:00'
                ],
                [
                    'openDay' => 'TUESDAY',
                    'closeDay' => 'TUESDAY',
                    'openTime' => '09:00',
                    'closeTime' => '17:00'
                ],
                [
                    'openDay' => 'WEDNESDAY',
                    'closeDay' => 'WEDNESDAY',
                    'openTime' => '09:00',
                    'closeTime' => '17:00'
                ],
                [
                    'openDay' => 'THURSDAY',
                    'closeDay' => 'THURSDAY',
                    'openTime' => '09:00',
                    'closeTime' => '17:00'
                ],
                [
                    'openDay' => 'FRIDAY',
                    'closeDay' => 'FRIDAY',
                    'openTime' => '09:00',
                    'closeTime' => '17:00'
                ],
            ]
        ];
        
        $location = Location::create([
            'affiliate_id'              => "123",
            'name'                      => $args['name'],
            'zip_postal_code'           => $args['zip_postal_code'],
            'latitude'                  => $args['latitude'],
            'longitude'                 => $args['longitude'],
            'telephone'                 => $args['telephone'],
            'telephone_ext'             => $args['telephone_ext'],
            'fax'                       => $args['fax'],
            'email'                     => $args['email'],
            'vanity_website_url'        => $args['vanity_website_url'],
            'slug'                      => $location_slug,
            'pre_open_display_date'     => $args['pre_open_display_date'],
            'opening_date'              => $args['opening_date'],
            'closing_date'              => $args['closing_date'],
            'daylight_savings_applies'  => $args['daylight_savings_applies'],
            'operating_hours'           => json_encode($business_hours),
            'timezone_id'               => $args['timezone_id']
         ]);
        
        return $location;
    }

}
