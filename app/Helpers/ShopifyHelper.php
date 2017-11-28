<?php

namespace App\Helpers;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

class ShopifyHelper
{
    private $client;

    function __construct()
    {
        $this->client = new GuzzleClient([
            'base_uri' => 'https://' . env('SHOPIFY_API_KEY') . ':' . env('SHOPIFY_API_PASSWORD') . '@' . env('SHOPIFY_API_STORE') . '.myshopify.com/admin/'
        ]);
    }

    public function getOrCreateCustomer($customer)
    {
        try
        {
            $result = $this->client->get('customers/search.json?query=email:' . $customer['email']);

            $customers = json_decode($result->getBody()->getContents())->customers;
            if(count($customers) > 0) {
                return $customers[0];
            }
        }
        catch (ClientException $e)
        {
            return null;
        }

        try
        {
            $result = $this->client->post('customers.json', [
                'json' => [
                    'customer' => $customer
                ]
            ]);

            return json_decode($result->getBody()->getContents())->customer;
        }
        catch (ClientException $e)
        {
            return null;
        }
    }

    public function getPriceRules()
    {
        try
        {
            $result = $this->client->get('price_rules.json');

            return json_decode($result->getBody()->getContents())->price_rules;
        }
        catch (ClientException $e)
        {
            return null;
        }
    }

    public function getUserMetafields($id)
    {
        try
        {
            $result = $this->client->get('customers/' . $id . '/metafields.json');

            return json_decode($result->getBody()->getContents())->metafields;
        }
        catch (ClientException $e)
        {
            return null;
        }
    }

    public function addUserMetafield($userId, $key, $value, $valueType)
    {
        try
        {
            $result = $this->client->post('customers/' . $userId . '/metafields.json', [
                'json' => [
                    'metafield' => [
                        'namespace' => 'global',
                        'key' => $key,
                        'value' => $value,
                        'value_type' => $valueType
                    ]
                ]
            ]);

            return true;
        }
        catch (ClientException $e)
        {
            return false;
        }
    }

    public function updateUserMetafield($userId, $id, $value)
    {
        try
        {
            $result = $this->client->post('customers/' . $userId . '/metafields.json', [
                'json' => [
                    'metafield' => [
                        'id' => intval($id),
                        'value' => intval($value)
                    ]
                ]
            ]);

            return true;
        }
        catch (ClientException $e)
        {
            return false;
        }
    }

    public function deleteMetafield($id)
    {
        try
        {
            $this->client->delete('metafields/' . $id . '.json');

            return true;
        }
        catch (ClientException $e)
        {
            return false;
        }
    }
}
