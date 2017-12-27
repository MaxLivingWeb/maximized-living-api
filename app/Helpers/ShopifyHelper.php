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

    public function getCustomer($id)
    {
        $result = $this->client->get('customers/' . $id . '.json');

        return json_decode($result->getBody()->getContents())->customer;
    }

    public function getOrCreateCustomer($customer)
    {
        //search for existing customer
        $result = $this->client->get('customers/search.json?query=email:' . $customer['email']);

        $customers = json_decode($result->getBody()->getContents())->customers;
        if(count($customers) > 0) {
            return $customers[0];
        }

        //no matching customer found, create new customer
        $result = $this->client->post('customers.json', [
            'json' => [
                'customer' => $customer
            ]
        ]);

        return json_decode($result->getBody()->getContents())->customer;
    }

    public function updateCustomer($customer)
    {
        $result = $this->client->put('customers/' . $customer['id'] . '.json', [
            'json' => [
                'customer' => $customer
            ]
        ]);

        return json_decode($result->getBody()->getContents())->customer;
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

    public function getOrdersCount($startDate = null, $endDate = null)
    {
        $query = [];
        if(!is_null($startDate) && !is_null($endDate)) {
            if($startDate == $endDate) {
                //dates are the same, add 23:59 to end time
                $endDate->add(new \DateInterval('PT23H59M59S'));
            }

            $query = array_merge($query, [
                'created_at_min' => $startDate->format('c'),
                'created_at_max' => $endDate->format('c'),
            ]);
        }

        $result = $this->client->get('orders/count.json', [
            'query' => $query
        ]);

        return json_decode($result->getBody()->getContents())->count;
    }

    public function getAllOrders($startDate = null, $endDate = null)
    {
        $PER_PAGE = 250;

        $count = $this->getOrdersCount($startDate, $endDate);

        $numPages = intval(ceil($count / $PER_PAGE));

        $allOrders = collect();
        for($i = 1; $i <= $numPages; ++$i) {
            $query = [
                'limit' => $PER_PAGE,
                'page'  => $i
            ];

            if(!is_null($startDate) && !is_null($endDate)) {
                if($startDate == $endDate) {
                    //dates are the same, add 23:59 to end time
                    $endDate->add(new \DateInterval('PT23H59M59S'));
                }

                $query = array_merge($query, [
                    'created_at_min' => $startDate->format('c'),
                    'created_at_max' => $endDate->format('c'),
                ]);
            }

            $result = $this->client->get('orders.json', [
                'query' => $query
            ]);

            $allOrders = $allOrders->merge(json_decode($result->getBody()->getContents())->orders);
        }

        return $allOrders;
    }
}
