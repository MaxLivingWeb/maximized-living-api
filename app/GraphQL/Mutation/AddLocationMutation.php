<?php

namespace App\GraphQL\Mutation;

use GraphQL;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\GraphQL\Type\LocationType;
use App\Address;
use App\Http\Controllers\TransactionalEmailController;

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
            if (is_array($var)) {
                $args[$key] = filter_var_array($var, FILTER_SANITIZE_STRING);
            } else {
                $args[$key] = filter_var($var, FILTER_SANITIZE_STRING);
            }
        }
        
        $location = Location::create([
            'name'                      => $args['name'],
            'telephone'                 => $args['telephone'],
            'telephone_ext'             => $args['telephone_ext'],
            'fax'                       => $args['fax'],
            'email'                     => $args['email'],
            'vanity_website_url'        => $args['vanity_website_url'],
            'vanity_website_id'         => $args['vanity_website_id'],
            'whitelabel'                => $args['whitelabel'],
            'pre_open_display_date'     => $args['pre_open_display_date'],
            'opening_date'              => $args['opening_date'],
            'closing_date'              => $args['closing_date'],
            'daylight_savings_applies'  => $args['daylight_savings_applies'],
            'business_hours'            => $args['business_hours']
         ]);
        
        $addresses = $args['addresses'];

        //takes all the addresses snd creates/updates as needed and attaches them to the location
        foreach($addresses as $address) {
            Address::attachAddress($location->id, $address);
        }

        //Email on location creation
	    if (!empty(env('ARCANE_NOTIFICATION_EMAIL'))) {
            $emailContent = array(
                null,
                null,
                $location,
                $addresses,
                'type'
            );
		    $sendEmail = new TransactionalEmailController();
		    $sendEmail->LocationEmail($emailContent);
	    }

        return $location;
    }
}
