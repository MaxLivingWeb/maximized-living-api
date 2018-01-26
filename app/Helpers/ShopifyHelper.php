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

    /**
     * Deletes a given customer from the Shopify store.
     *
     * @param array $customer An associative array of customer info.
     * @return array
     */
    public function deleteCustomer($customer)
    {
        $result = $this->client->delete('customers/' . $customer['id'] . '.json', [
            'json' => [
                'customer' => $customer
            ]
        ]);

        return json_decode($result->getBody()->getContents())->customer;
    }

    public function getPriceRules()
    {
        $result = $this->client->get('price_rules.json');

        return json_decode($result->getBody()->getContents())->price_rules;
    }

    public function getPriceRule($id)
    {
        try
        {
            $result = $this->client->get('price_rules/' . $id . '.json');

            return json_decode($result->getBody()->getContents())->price_rule;
        }
        catch (ClientException $e)
        {
            return null;
        }
    }

    public function addCustomerTag($id, $tag)
    {
        $customer = $this->getCustomer($id);

        $tags = $customer->tags . ',' . $tag;

        $this->client->put('customers/' . $id . '.json', [
            'json' => [
                'customer' => [
                    'id' => $id,
                    'tags' => $tags
                ]
            ]
        ]);
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
            $this->client->post('customers/' . $userId . '/metafields.json', [
                'json' => [
                    'metafield' => [
                        'id' => (int)$id,
                        'value' => (int)$value
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

    /**
     * Retrieve a count of all the orders using Shopify's AdminAPI
     * https://help.shopify.com/api/reference/order
     * @param $startDate
     * @param $endDate
     * @param $status | 'open' (default), 'closed', 'cancelled', 'any'
     * @return Shopify Orders Count
     */
    public function getOrdersCount($startDate = null, $endDate = null, $status = null)
    {
        $query = [
            'status' => $status ?? 'any' //to override the default value of 'open'
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

        $result = $this->client->get('orders/count.json', [
            'query' => $query
        ]);

        return json_decode($result->getBody()->getContents())->count;
    }


    /**
     * Retrieve a list of Orders using Shopify's AdminAPI
     * https://help.shopify.com/api/reference/order
     * @param $startDate
     * @param $endDate
     * @param $status | 'open' (default), 'closed', 'cancelled', 'any'
     * @return Shopify Orders
     */

    public function getAllOrders($startDate = null, $endDate = null, $status = null)
    {
        $status = $status ?? 'any'; //to override the default value of 'open'

        $PER_PAGE = 250;

        $count = $this->getOrdersCount($startDate, $endDate, $status);

        $numPages = (int)ceil($count / $PER_PAGE);

        $allOrders = collect();
        for($i = 1; $i <= $numPages; ++$i) {
            $query = [
                'status' => $status,
                'limit'  => $PER_PAGE,
                'page'   => $i
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
    
    public function getProducts()
    {
        try
        {
            $query = http_build_query(
                array_filter([
                    'limit' => 250
                ])
            );
        
            $result = $this->client->get('products.json?' . $query);
        
            return json_decode($result->getBody()->getContents(), true)['products'];
        }
        catch (ClientException $e)
        {
            return $result;
        }
    }
}
