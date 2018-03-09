<?php

namespace App\GraphQL\Mutation;

use App\Http\Controllers\GmbController as GMB;
use GraphQL;
use Folklore\GraphQL\Support\Mutation;
use App\Location;
use App\GraphQL\Type\LocationType;
use App\Address;
use App\Http\Controllers\TransactionalEmailController;

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

        if(empty($args['gmb_id']) ) {
            $args['gmb_id'] = '';
        }

        //Location before being updated for notification email
        $locationBeforeUpdate = Location
            ::where(
                'id', $args['id']
            )->orWhere(
                'vanity_website_id', $args['vanity_website_id']
            )->first();

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
                'business_hours'            => $args['business_hours'],
                'gmb_id'                    => $args['gmb_id']
            ]);

        $updated_location = Location
            ::where('vanity_website_id', $args['vanity_website_id'])
            ->orWhere('id', $args['id'])
            ->first();

        $addresses = $args['addresses'];

        $contact = new TransactionalEmailController();

        if(empty($addresses)) {
            return $args;
        }

        $address_exists = Address
            ::where([
                'address_1'         => $addresses[0]['address_1'],
                'address_2'         => $addresses[0]['address_2'],
                'latitude'          => $addresses[0]['latitude'],
                'longitude'         => $addresses[0]['longitude'],
                'zip_postal_code'   => $addresses[0]['zip_postal_code']
            ])->first();

        if(!empty($args['gmb_id']) ) {
            //update the gmb record
            $gmb = new GMB();
            $gmb->update($updated_location);
        }

        //Location after being updated for notification email
        $locationAfterUpdate = Location
            ::where(
                'id', $args['id']
            )->orWhere(
                'vanity_website_id', $args['vanity_website_id']
            )->first();

        //if the address exists, just get out
        if(!empty($address_exists) && env('APP_ENV') !== 'local') {

            //Email on location address update

            return $args;
        }

        //detach before add the new addresses
        $updated_location->addresses()->detach();

        //takes all the addresses snd creates/updates as needed and attaches them to the location
        foreach($addresses as $address) {
            Address::attachAddress($updated_location->id, $address);
        }

        //Email on location address update
        $content = '<br><h3><a href="'.$locationAfterUpdate->vanity_website_url.'" target="_blank">'.$locationAfterUpdate->name.'</a> has been updated!</h3>';
        $content .= 'Location Name: '.$locationAfterUpdate->name;
        $content .= '<br>Telephone Number: '.$locationAfterUpdate->telephone;
        $content .= '<br>Telephone Ext: '.$locationAfterUpdate->telephone_ext;
        $content .= '<br>Fax Number: '.$locationAfterUpdate->fax;
        $content .= '<br>Email: '.$locationAfterUpdate->email;
        $content .= '<br>Website: '.$locationAfterUpdate->vanity_website_url;
        $content .= '<br>Address 1: '.$addresses[0]['address_1'];
        $content .= '<br>Address 2: '.$addresses[0]['address_2'];
        $content .= '<br>City: '.$addresses[0]['city'];
        $content .= '<br>Region: '.$addresses[0]['region'];
        $content .= '<br>Postal Code: '.$addresses[0]['zip_postal_code'];
        $content .= '<br>Country: '.$addresses[0]['country'];


        $content .= '<br><br><h4>Previous information:</h4>';
        $content .= 'Location Name: '.$locationBeforeUpdate->name;
        $content .= '<br>Telephone Number: '.$locationBeforeUpdate->telephone;
        $content .= '<br>Telephone Ext: '.$locationBeforeUpdate->telephone_ext;
        $content .= '<br>Fax Number: '.$locationBeforeUpdate->fax;
        $content .= '<br>Email: '.$locationBeforeUpdate->email;
        $content .= '<br>Website: '.$locationBeforeUpdate->vanity_website_url;
        $content .= '<br>Address 1: '.$locationBeforeUpdate->addresses()->address_1;
        $content .= '<br>Address 2: '.$addressesBeforeUpdate[0]['address_2'];
        $content .= '<br>City: '.$addressesBeforeUpdate[0]['city'];
        $content .= '<br>Region: '.$addressesBeforeUpdate[0]['region'];
        $content .= '<br>Postal Code: '.$addressesBeforeUpdate[0]['zip_postal_code'];
        $content .= '<br>Country: '.$addressesBeforeUpdate[0]['country'];



        $email = array(
            'to_email' => 'l.stewart@arcane.ws',
            'reply_to' => 'noreply@maxliving.com',
            'email_subject' => 'Update for MaxLiving Location: '.$locationAfterUpdate->name,
            'form_name' => 'Update for MaxLiving Location',
            'content' => $content
        );
        $contact->apiSave($email);

        dd($contact);

        if ($location === 1) {
            return $args;
        }

        return $location;
    }

}
