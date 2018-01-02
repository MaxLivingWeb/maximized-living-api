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
                $args[$key] = filter_var_array($var, FILTER_SANITIZE_STRING);
            } else {
                $args[$key] = filter_var($var, FILTER_SANITIZE_STRING);
            }
        }
        
        $location = Location
            ::where(
                'id', $args['id']
            )->orWhere(
                'vanity_website_id', $args['vanity_website_id']
            )
            ->update([
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
                'business_hours'            => $args['business_hours']
            ]);

        $updated_location = Location::where('vanity_website_id', $args['vanity_website_id'])->first();
    
        $addresses = $args['addresses'];

        //detach before add the new addresses
        $updated_location->addresses()->detach();

        //takes all the addresses snd creates/updates as needed and attaches them to the location
        foreach($addresses as $address) {
            Address::attachAddress($updated_location->id, $address);
        }

        if ($location === 1) {
            return $args;
        }

        return $location;
    }

}
