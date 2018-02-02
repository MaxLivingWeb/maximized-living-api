<?php

namespace App\Extensions\CacheableApi;

use GuzzleHttp;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use App\Helpers\SlackHelper;

class CacheableApi
{
    /**
     * The GuzzleHttp client (already configured with the proper base uri).
     *
     * @var GuzzleHttp\Client
     */
    public $client;

    /**
     * The number of minutes to cache results for. Defaults to the .env value (or 5 minutes if there is no .env value).
     *
     * @var integer
     */
    protected $cacheTime;

    /**
     * Custom name prepend for the cache. For example, if we instantiate a cache that should be kept for 600 minutes,
     * we don't want to overwrite the normal 5-minute cache. By default there is no prepend, however if a custom cache
     * time is passed, one will be created.
     *
     * @var string
     */
    protected $cacheName;

    /**
     * Valid Guzzle client methods (ie get, post, etc.).
     *
     * @var array
     */
    protected $validMethods = [
        'get', 'post', 'put', 'patch', 'delete' // TODO: expand?
    ];

    /**
     * The base URI associated with this instance of class.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * Whether or not the configured Cache driver supports tagging.
     *
     * @var bool
     */
    protected $cacheHasTags;

    /**
     * CacheableApi constructor.
     *
     * @param $baseUri The base URI for the client to make all requests off.
     * @param integer $cacheTime The number of minutes to cache request results.
     */
    public function __construct($baseUri, $cacheTime = NULL)
    {
        $this->client = new GuzzleHttp\Client(['base_uri' => $baseUri]);
        $this->cacheTime = $cacheTime ?? config('cacheable_api.default_cache_time') ?? 5;
        $this->cacheName = $cacheTime ? (string)$cacheTime . '_' : '';
        $this->baseUri = $baseUri;
        $this->cacheHasTags = method_exists(Cache::getStore(), 'tags');
    }

    /**
     * Tries to access the given endpoint via the given method. If the method is not valid, function will return FALSE.
     *
     * @param string $method The method by which to query.
     * @param string $endpoint The endpoint to query.
     * @param bool $cache Whether to cache the result.
     * @return stdClass|bool|\Exception
     */
    public function query(string $method, string $endpoint, $data = [], $cache = FALSE)
    {
        try {
            if(!in_array($method, $this->validMethods)) {
                return FALSE;
            }

            if(Cache::has($this->cacheName . $endpoint) && $cache) {
                return Cache::get($this->cacheName . $endpoint);
            }

            if($method === 'get') {
                $result = $this->client->{$method}($endpoint);
            } else {
                $result = $this->client->{$method}($endpoint, $data);
            }

            $resultContents = $result->getBody()->getContents();

            if($cache) {
                if($this->cacheHasTags) {
                    Cache::tags([$this->cacheName, $this->baseUri])
                        ->put(
                            $this->cacheName . $endpoint,
                            $resultContents,
                            $this->cacheTime
                        );
                } else {
                    Cache::put(
                        $this->cacheName . $endpoint,
                        $resultContents,
                        $this->cacheTime
                    );
                }
            }

            try {
                $callLimit = $result->getHeader('HTTP_X_SHOPIFY_SHOP_API_CALL_LIMIT')[0];
                if (!empty($callLimit) && substr($callLimit, 0, 2) > 70) {
                    SlackHelper::slackNotification('*Error:* Shopify API close to limit. ' . $callLimit);
                }
            }
            catch(\Exception $e) {
                // do nothing
            }

            return $resultContents;
        }
        catch(\Exception $e) {
            return $e; // TODO: what do we do with exceptions? Abort? Return NULL? Return the exception?
        }
    }

    /**
     * Runs a GET query on the given endpoint.
     *
     * @param string $endpoint The endpoint to query.
     * @param bool $cache Whether to cache the result.
     * @return stdClass|bool
     */
    public function get($endpoint, $cache = FALSE)
    {
        return $this->query('get', $endpoint, [], $cache);
    }

    /**
     * Runs a POST query on the given endpoint.
     *
     * @param string $endpoint The endpoint to query.
     * @param array $data An array of data to include.
     * @return stdClass|bool
     */
    public function post($endpoint, $data = [])
    {
        return $this->query('post', $endpoint, $data, FALSE);
    }

    /**
     * Runs a PUT query on the given endpoint.
     *
     * @param string $endpoint The endpoint to query.
     * @param array $data An array of data to include.
     * @return stdClass|bool
     */
    public function put($endpoint, $data = [])
    {
        return $this->query('put', $endpoint, $data, FALSE);
    }

    /**
     * Runs a PATCH query on the given endpoint.
     *
     * @param string $endpoint The endpoint to query.
     * @param array $data An array of data to include.
     * @return stdClass|bool
     */
    public function patch($endpoint, $data = [])
    {
        return $this->query('patch', $endpoint, $data, FALSE);
    }

    /**
     * Runs a DELETE query on the given endpoint.
     *
     * @param string $endpoint The endpoint to query.
     * @param array $data An array of data to include.
     * @return stdClass|bool
     */
    public function delete($endpoint, $data = [])
    {
        return $this->query('delete', $endpoint, $data, FALSE);
    }

    /**
     * Set the cache time for this instance of the API in minutes.
     *
     * @param integer $cacheTime The number of minutes to set the cache time to.
     * @return bool
     */
    public function setCacheTime($cacheTime)
    {
        $this->cacheTime = $cacheTime;
        $this->cacheName = (string)$cacheTime . '_';
        return TRUE;
    }

    /**
     * Flushes all cache results that are stored for the same base URI. If $byTime is TRUE, only results for the same
     * base URI that are ALSO stored for the same amount of time are flushed.
     *
     * @param bool $byTime Whether or not to delete by time in addition to deleting by base uri.
     * @return mixed
     */
    public function flushCache($byTime = FALSE)
    {
        if(!$this->cacheHasTags){
            return FALSE;
        }

        $tags = [$this->baseUri];
        if($byTime) {
            $tags[] = $this->cacheName;
        }

        return Cache::tags($tags)
            ->flush();
    }
}

