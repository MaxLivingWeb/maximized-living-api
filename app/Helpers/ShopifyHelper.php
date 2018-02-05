<?php

namespace App\Helpers;

use App\Extensions\CacheableApi\CacheableApi;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;

class ShopifyHelper extends CacheableApi
{
    /**
     * ShopifyAdminAPI constructor.
     *
     * @param integer $cacheTime The number of minutes to cache request results.
     */
    public function __construct($cacheTime = NULL)
    {
        parent::__construct(
            'https://' . env('SHOPIFY_API_KEY') . ':' . env('SHOPIFY_API_PASSWORD') . '@' . env('SHOPIFY_API_STORE') . '.myshopify.com/admin/',
            $cacheTime ?? env('SHOPIFY_API_CACHE_TIME', 5)
        );
    }

    public function getCustomer($id)
    {
        $result = $this->get('customers/' . $id . '.json', TRUE);

        return json_decode($result)->customer;
    }

    public function getOrCreateCustomer($customer)
    {
        //search for existing customer
        $result = $this->get('customers/search.json?query=email:' . $customer['email']);

        $customers = json_decode($result)->customers;
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

    public function getCustomers($ids)
    {
        $endpoint = 'customers.json';

        try {
            $customers = collect([]);

            collect($ids)
                ->chunk(30)
                ->each(function($chunk) use ($endpoint, $customers){
                    $params = [
                        'query' => [
                            'ids' => $chunk->implode(',')
                        ]
                    ];

                    $cacheString = $this->cacheName . $endpoint . serialize($params);

                    if(Cache::has($cacheString)) {
                        $customers->push(json_decode(Cache::get($cacheString)));
                    } else {
                        $result = $this->client->get($endpoint, $params);
                        try {
                            $customers->push(json_decode($result->getBody()->getContents())->customers);
                            Cache::put($cacheString, $result->getBody()->getContents(), $this->cacheTime);
                        }
                        catch(\Exception $e) {
                            dd($result->getBody()->getContents());
                        }
                    }
                });

            return $customers->flatten();
        }
        catch (ClientException $e)
        {
            return NULL;
        }
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
        $result = $this->get('price_rules.json', TRUE);

        return json_decode($result->getBody()->getContents())->price_rules;
    }

    public function getPriceRule($id)
    {
        try
        {
            $result = $this->get('price_rules/' . $id . '.json', TRUE);

            return json_decode($result)->price_rule;
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
            $result = $this->get('customers/' . $id . '/metafields.json', TRUE);

            return json_decode($result)->metafields;
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

    /**
     * Retrieve a count of all the products using Shopify's AdminAPI.
     *
     * @return Shopify Orders Count
     */
    public function getProductCount()
    {
        $result = $this->get('products/count.json', TRUE);
        return json_decode($result)->count;
    }
    
    public function getProducts()
    {
        try
        {
            $count = $this->getProductCount();
            $per_page = 250;
            $numPages = (int)ceil($count / $per_page);

            $products = [];
            for($i = 1; $i <= $numPages; ++$i) {
                $result = $this->client->get('products.json', [
                    'query' => [
                        'limit'  => $per_page,
                        'page'   => $i
                    ]
                ]);

                $products[] = json_decode($result->getBody()->getContents())->products;
            }

            return array_merge(...$products);
        }
        catch (ClientException $e)
        {
            return $result;
        }
    }
}
