<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\GraphQL\Type\LocationType;
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
        $locationType = new LocationType();
        return $locationType->fields();
    }
    
    public function resolve($root, $args)
    {
        foreach ($args as $key => $var) {
            $args[$key] = filter_var($var, FILTER_SANITIZE_STRING);
        }
        dd($args['addresses']);
    
        $cityId = City::checkCity( $args['country'], $args['region'], $args['city'] );
        
        $addresses = [
             [
                'address_1' => 'Test Street',
                'addressType' => '1'
            ],
             [
                 'address_1' => 'Other Street',
                'addressType' => '2'
            ]
        ];
        
        dd(json_encode($addresses), $args['addresses']);
        
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
            'pre_open_display_date'     => $args['pre_open_display_date'],
            'opening_date'              => $args['opening_date'],
            'closing_date'              => $args['closing_date'],
            'daylight_savings_applies'  => $args['daylight_savings_applies'],
            'operating_hours'           => json_encode($business_hours)
         ]);
        
        return $location;
    }

}

//{addLocation(addresses:%22[{\%22address_1\%22:\%22richmond%20st\%22,\%22address_2\%22:\%22unit%203\%22,\%22type_id\%22:1},{\%22address_1\%22:\%22oxford%20st\%22,\%22address_2\%22:\%22unit%209\%22,\%22type_id\%22:2}]%22,region_id:1,city:%22Londy%22,daylight_savings_applies:0,pre_open_display_date:%2202-02-02%22,opening_date:%2202-02-02%22,closing_date:%2208-06-04%22,name:%22tommylandPART2%22,zip_postal_code:%2290210%22,latitude:45.8543456,longitude:-91.1234564,telephone:%22519-472-1718%22,telephone_ext:%2298%22,fax:%2212345%22,email:%22tom@tom.com%22,vanity_website_url:%22vanity_url%22,timezone_id:1){name,latitude}}
//
//mutation {
//    addLocation(address:[{
//        address_1: "Richmond St",
//        address_2: "unit 3",
//        zip_postal_code: "n5z1y1",
//        latitude: "45.8543456",
//        longitude: "-91.1234564",
//        city: "London"
//    },
//    {
//        address_1: "Richmond St",
//        address_2: "unit 4",
//        zip_postal_code: "n5z1y1",
//        latitude: "45.8543456",
//        longitude: "-91.1234564",
//        city: "Toronto"
//    }])
//}
