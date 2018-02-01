<?php

namespace App\Http\Controllers;

use App\CommissionGroup;
use Illuminate\Http\Request;
use GuzzleHttp;
use Mockery\Exception;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Process\Process;
use App\Location;

class GmbController extends Controller
{
    private $client_id;
    private $client_secret;
    private $refresh_token;
    private $access_token;

    /**
     * GmbController constructor.
     */
    public function __construct() {

        //set up the authorization
        $this->client_id = env('GOOGLE_CLIENT_ID');
        $this->client_secret = env('GOOGLE_CLIENT_SECRET');
        $this->refresh_token = env('GOOGLE_REFRESH_TOKEN');


        $client = new \GuzzleHttp\Client();
        $response = $client->request(
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

        if(empty($this->access_token) ) {
            return;
        }

        $gmb_data = $this->format_for_gmb($location);

        //send that formatted gmb_data to gmb
        //https://mybusiness.googleapis.com/v3/accounts/account_name/locations/locationId?languageCode=language&validateOnly=True|False&fieldMask=field1,field2,etc.
    }

    /**
     * Queries a single GMB location
     * @param $gmb_location_id
     */
    public function get($gmb_location_id) {

        if(empty($this->access_token) ) {
            return;
        }

        //query API based on its $gmb_locations_id
        //https://mybusiness.googleapis.com/v3/accounts/account_name/locations/locationId
    }

    /**
     *
     */
    public function get_all() {

        if(empty($this->access_token) ) {
            return;
        }

        //query API based on its $gmb_locations_id
        //https://mybusiness.googleapis.com/v3/{name=accounts/*}/locations:batchGet
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

        $gmb_data = '';

        //format gmb_data to the proper format

        return $gmb_data;
    }
}
