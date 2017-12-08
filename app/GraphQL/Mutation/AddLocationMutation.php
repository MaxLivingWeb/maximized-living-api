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
        /*
         * query format example
         * http://max-living-locations-2.dev/graphql?query=mutation+mutation{addLocation(addresses:%22[{\%22address_1\%22:\%22richmond%20st\%22,\%22address_2\%22:\%22unit%203\%22,\%22type_id\%22:1},{\%22address_1\%22:\%22oxford%20st\%22,\%22address_2\%22:\%22unit%209\%22,\%22type_id\%22:2}]%22,region_id:1,city:%22Londy%22,daylight_savings_applies:false,pre_open_display_date:%2202-02-02%22,opening_date:%2202-02-02%22,closing_date:%2208-06-04%22,name:%22tommylandPART2%22,zip_postal_code:%2290210%22,latitude:45.8543456,longitude:-91.1234564,telephone:%22519-472-1718%22,telephone_ext:%2298%22,fax:%2212345%22,email:%22tom@tom.com%22,vanity_website_url:%22vanity_url%22,timezone_id:1){name,latitude}}
        */
    
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
            'operating_hours'           => $args['businessHours']
         ]);
        
        $addresses = $args['addresses'];

        //takes all the addresses snd creates/updates as needed and attaches them to the location
        foreach($addresses as $address) {
            Address::attachAddress($location->id, $address);
        }
    
        return $location;
    }
}
