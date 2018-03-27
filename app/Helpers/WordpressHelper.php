<?php

namespace App\Helpers;

use GuzzleHttp;
use GuzzleHttp\Exception\ClientException;

class WordpressHelper
{
    private $client;

    function __construct()
    {
        $this->client = new GuzzleHttp\Client(['base_uri' => env('WORDPRESS_API_URL')]);
    }

    public function createUser($params)
    {
        try {
            $result = $this->client->post('wp-json/authportal/user/create', [
                'json' => $params
            ]);

            return json_decode($result->getBody()->getContents());
        }
        catch(ClientException $e) {
            return null;
        }
    }

}
