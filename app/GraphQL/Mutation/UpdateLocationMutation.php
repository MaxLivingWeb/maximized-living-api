<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\GraphQL\Type\LocationType;
use App\Address;

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
        $locationType = new LocationType();
        return $locationType->fields();
    }

    public function resolve($root, $args)
    {
        foreach ($args as $key => $var) {
            if (is_array($var)) {
                $var = json_encode($var);
            }
            $args[$key] = filter_var($var, FILTER_SANITIZE_STRING);
        }
        
        dd($args);
    
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

        //TODO Need to get slug updating working
        $location = Location
            ::where(
                'id', $args['id']
            )
            ->update([
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
            Address::attachAddress($args['id'], $address);
        }
        
        if ($location === 1) {
            return $args;
        }
        return $location;
    }

}
