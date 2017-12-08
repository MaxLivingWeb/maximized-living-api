<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\GraphQL\Type\LocationType;
use App\Address;

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
        foreach ($args as $key => $var) {
            $args[$key] = filter_var($var, FILTER_SANITIZE_STRING);
        }
        
        dd($args);
    
        $data = [
            'name' => 'Alexville',
            'daylight_savings_applies' => 1,
            'telephone' => '519-472-1718',
            'telephone_ext' => '98',
            'fax' => '555-123-4567',
            'email' => 'test@example.com',
            'vanity_website_url' => 'vanity_url',
            'pre_open_display_date' => '02-02-02',
            'opening_date' => '02-02-02',
            'closing_date' => '08-06-04',
            'addresses' => [
                'address_1' => 'Test Street',
                'city' => 'London',
                'region' => 'Ontario',
                'country' => 'Canada',
                'zip_postal_code' => 'N5Z1Y1',
                'latitude' => '45.8543456',
                'longitude' => '-91.1234564',
                'addressType' => '1'
            ],
            [
                'address_1' => 'Other Street',
                'city' => 'New York',
                'region' => 'New York',
                'country' => 'United States of America',
                'zip_postal_code' => '90210',
                'latitude' => '45.8543456',
                'longitude' => '-91.1234564',
                'addressType' => '2'
            ],
            'businessHours' => [
                [
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
            'telephone'                 => $args['telephone'],
            'telephone_ext'             => $args['telephone_ext'],
            'fax'                       => $args['fax'],
            'email'                     => $args['email'],
            'vanity_website_url'        => $args['vanity_website_url'],
            'pre_open_display_date'     => $args['pre_open_display_date'],
            'opening_date'              => $args['opening_date'],
            'closing_date'              => $args['closing_date'],
            'daylight_savings_applies'  => $args['daylight_savings_applies'],
            'operating_hours'           => $args['business_hours']
         ]);
        
        $addresses = $args['addresses'];

        //takes all the addresses snd creates/updates as needed and attaches them to the location
        foreach($addresses as $address) {
            Address::attachAddress($location->id, $address);
        }
    
        return $location;
    }
}
