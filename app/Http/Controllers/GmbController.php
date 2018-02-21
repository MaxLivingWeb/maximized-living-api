<?php

namespace App\Http\Controllers;

use App\CommissionGroup;
use Illuminate\Http\Request;
use GuzzleHttp;
use Mockery\Exception;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log as Log;

class GmbController extends Controller
{
    private $client_id;
    private $client_secret;
    private $refresh_token;
    private $client;
    private $access_token;

    /**
     * GmbController constructor.
     */
    public function __construct() {

        //set up the authorization
        $this->client_id = env('GOOGLE_CLIENT_ID');
        $this->client_secret = env('GOOGLE_CLIENT_SECRET');
        $this->refresh_token = env('GOOGLE_REFRESH_TOKEN');

        $this->client = new \GuzzleHttp\Client();

        $response = $this->client->request(
            'POST',
            'https://www.googleapis.com/oauth2/v4/token',
            array(
                'form_params' => array(
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refresh_token
                )
            )
        );

        if(!empty(json_decode($response->getBody() )->access_token) ) {
            $this->access_token = json_decode($response->getBody() )->access_token;
        }
    }

    /**
     * Performs the update to GMB
     *
     * @param $location
     */
    public function update($location = null) {

        if(empty($this->access_token) || empty($location) ) {
            return;
        }

        $gmb_data = $this->format_for_gmb($location);

        //send that formatted gmb_data to gmb
        //https://mybusiness.googleapis.com/v3/accounts/account_name/locations/locationId?languageCode=language&validateOnly=True|False&fieldMask=field1,field2,etc.
        //it will look something like this
        try {
            $response = $this->client->request(
                'PATCH',
                'https://mybusiness.googleapis.com/v3/accounts/109466447190993053012/locations/'.$location->gmb_id,
                array(
                    'headers' => array(
                        'Authorization' => "Bearer $this->access_token"
                    ),
                    'body' => $gmb_data,
                    'http_errors' => false
                )
            );

        } catch (Exception $e) {
            Log::error($e);
        }

        return $response->getBody()->getContents();
    }

    /**
     * Queries a single GMB location
     * @param $gmb_location_id
     */
    public function get($gmb_location_id) {

        //query API based on its $gmb_locations_id
        //https://mybusiness.googleapis.com/v3/accounts/account_name/locations/locationId

        if (empty($this->access_token)) {
            return;
        }

        //it will look something like this
        try {
            $response = $this->client->request(
                'GET',
                'https://mybusiness.googleapis.com/v3/accounts/109466447190993053012/locations/'.$gmb_location_id,
                array(
                    'headers' => array(
                        'Authorization' => "Bearer $this->access_token"
                    )
                )
            );

        } catch (Exception $e) {
            Log::error($e);
        }

        return $response->getBody()->getContents();
    }

    /**
     *
     */
    public function get_all() {

        //query API based on its $gmb_locations_id
        //https://mybusiness.googleapis.com/v3/{name=accounts/*}/locations:batchGet

        if (empty($this->access_token)) {
            return;
        }

        //it will look something like this
        try {
            $response = $this->client->request(
                'GET',
                'https://mybusiness.googleapis.com/v3/accounts/109466447190993053012/locations',
                array(
                    'headers' => array(
                        'Authorization' => "Bearer $this->access_token"
                    )
                )
            );

        } catch (Exception $e) {
            Log::error($e);
        }

        return $response->getBody()->getContents();
    }

    /**
     * Formats the data in the format for GMB
     *
     * @param $locations
     * @return string
     */
    private function format_for_gmb($location) {

        if(empty($location) ) {
            return;
        }

        //get the gmb store code
        $gmb_entry = json_decode($this->get($location->gmb_id) );

        if($gmb_entry === false) {
            return;
        }

        $gmb_data = '{';

        $gmb_data .= '"languageCode" : "EN",';

        //store_code
        $gmb_data .= '"storeCode" : "'.$gmb_entry->storeCode.'",';

        //location name
        $gmb_data .= '"locationName" : "'.$location->name.'",';

        //primary phone
        $gmb_data .= '"primaryPhone" : "'.$location->telephone.' '.$location->telephone_ext.'",';

        //address
        $gmb_data .= '"address": {';

        //address lines
        $gmb_data .= '"addressLines": [';
        $gmb_data .= '"'.$location->addresses[0]->address_1.'",';
        $gmb_data .= '"'.$location->addresses[0]->address_2.'"';
        $gmb_data .= '],';

        //locality
        $gmb_data .= '"locality" : "'.$location->addresses[0]->city->name.'",';

        //postal code
        $gmb_data .= '"postalCode" : "'.$location->addresses[0]->zip_postal_code.'",';

        //administrative area (which I'm pretty sure corresponds to regions)
        $gmb_data .= '"administrativeArea" : "'.$location->addresses[0]->city->region->abbreviation.'",';

        //country
        $gmb_data .= '"country" : "'.$location->addresses[0]->city->region->country->abbreviation.'"';

        //close address
        $gmb_data .= '},';

        //website url
        $gmb_data .= '"websiteUrl" : "'.$location->vanity_website_url.'",';

        //business hours
        $gmb_data .= $this->format_business_hours($location->business_hours);

        //primary category
        $gmb_data .= '"primaryCategory" : { "name":"Chiropractor", "categoryId": "gcid:chiropractor" }';

        $gmb_data .= '}';

        return $gmb_data;
    }

    /**
     * @param $business_hours
     * @return string
     */
    private function format_business_hours($business_hours) {

        $f_business_hours = '"regularHours": {';
        $f_business_hours .= '"periods": [';

        $business_hours_array = json_decode(html_entity_decode($business_hours) );

        foreach($business_hours_array as $bh) {
            if($bh[1] !== "open") {
                continue;
            }

            foreach($bh[2] as $time) {
                $f_business_hours .= '{';
                $f_business_hours .= '"openDay": "'.strtoupper($bh[0]).'",';
                $f_business_hours .= '"closeDay": "'.strtoupper($bh[0]).'",';
                $f_business_hours .= '"openTime": "'.$this->to_twenty_four_hour($time->open).'",';
                $f_business_hours .= '"closeTime": "'.$this->to_twenty_four_hour($time->closed).'"';
                $f_business_hours .= '},';
            }
        }

        $f_business_hours .= ']';
        $f_business_hours .= '},';

        return $f_business_hours;
    }

    /**
     * @param $hour_string
     * @return false|string
     */
    private function to_twenty_four_hour($hour_string) {

        return date("H:i", strtotime($hour_string) );
    }
}
