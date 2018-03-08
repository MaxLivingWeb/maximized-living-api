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
    public function update($location) {

        if(empty($this->access_token) || empty($location) ) {
            return;
        }

        $gmb_data = $this->format_for_gmb($location);

        //send that formatted gmb_data to gmb
        //https://mybusiness.googleapis.com/v3/accounts/account_name/locations/locationId?languageCode=language&validateOnly=True|False&fieldMask=field1,field2,etc.
        //109466447190993053012 is the account ID of ML within our GMB email
        try {
            $response = $this->client->request(
                'PATCH',
                'https://mybusiness.googleapis.com/v3/accounts/117651769791383192578/locations/'.$location->gmb_id,
                array(
                    'headers' => array(
                        'Authorization' => "Bearer $this->access_token"
                    ),
                    'body' => $gmb_data
                )
            );

            return $response->getBody()->getContents();

        } catch (Exception $e) {
            Log::error($e);
        }
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
                'https://mybusiness.googleapis.com/v4/accounts/117651769791383192578/locations/'.$gmb_location_id,
                array(
                    'headers' => array(
                        'Authorization' => "Bearer $this->access_token"
                    )
                )
            );

            return $response->getBody()->getContents();

        } catch (Exception $e) {
            Log::error($e);
        }
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
                'https://mybusiness.googleapis.com/v4/accounts/117651769791383192578/locations',
                array(
                    'headers' => array(
                        'Authorization' => "Bearer $this->access_token"
                    )
                )
            );

            //grabbing locations only with the ML label
            $locs = json_decode($response->getBody()->getContents(), true);
            $ml_locations = [];

            foreach ($locs['locations'] as $l) {
                if (array_key_exists('labels', $l) && in_array("ML", $l['labels'])) {
                    array_push($ml_locations, $l);
                }
            }

            return json_encode($ml_locations);
        }
        catch (Exception $e) {
            Log::error($e);
        }
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

        //if the GMB entry has labels associated with it, keep the labels on
        $gmb_labels = [];

        if(is_array($gmb_entry->labels)) {
            foreach($gmb_entry->labels as $labels) {
                array_push($gmb_labels, $labels);
            }
        }

        $json_gmb_data = json_encode([
            "languageCode"  => "EN",
            "storeCode"     => $gmb_entry->storeCode,
            "locationName"  => $location->name,
            "primaryPhone"  => $location->telephone.' '.$location->telephone_ext,
            "address"       => [
                "addressLines" => [
                    $location->addresses[0]->address_1,
                    $location->addresses[0]->address_2
                ],
                "locality"              => $location->addresses[0]->city->name,
                "postalCode"            => $location->addresses[0]->zip_postal_code,
                "administrativeArea"    => $location->addresses[0]->city->region->abbreviation,
                "country"               => $location->addresses[0]->city->region->country->abbreviation
            ],
            "websiteUrl"        => $location->vanity_website_url,
            "regularHours"      => $this->format_business_hours($location->business_hours),
            "primaryCategory"   => [
                "name"          => "Chiropractor",
                "categoryId"    => "gcid:chiropractor"
            ],
            "labels" => $gmb_labels
        ]);

        return $json_gmb_data;
    }

    /**
     * @param $business_hours
     * @return array
     */
    private function format_business_hours($business_hours) {
        $business_hours_array = json_decode(html_entity_decode($business_hours) );
        $f_business_hours = [];

        foreach($business_hours_array as $bh) {
            if($bh[1] !== "open") {

                continue;
            }

            foreach($bh[2] as $time) {

                $day_hours = [
                    "openDay"   => strtoupper($bh[0]),
                    "closeDay"  => strtoupper($bh[0]),
                    "openTime"  => $this->to_twenty_four_hour($time->open),
                    "closeTime" => $this->to_twenty_four_hour($time->closed)
                ];

                array_push($f_business_hours, $day_hours);
            }
        }

        $formatted_bh = [
                "periods" => $f_business_hours
        ];

        return (array) $formatted_bh;
    }

    /**
     * @param $hour_string
     * @return false|string
     */
    private function to_twenty_four_hour($hour_string) {

        return date("H:i", strtotime($hour_string) );
    }
}
