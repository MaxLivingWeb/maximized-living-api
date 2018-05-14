<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class SearchHelper
{
    /**
     * Searches the database for products matching the given string.
     *
     * @param string $query The string to search for.
     * @return \Illuminate\Support\Collection
     */
    public static function productSearch(string $query): \Illuminate\Support\Collection
    {
        $skuSearch = self::_skuSearch($query);
        $nameSearch = self::_nameSearch($query);
        $tagSearch = self::_tagSearch($query);
        $descriptionSearch = self::_descriptionSearch($query);
        
        $results = array_merge($skuSearch, $nameSearch, $tagSearch, $descriptionSearch);
        $unique = array_unique($results);

        return self::_shopifyDataByIds($unique);
    }

    /**
     * Matches specific SKUs that may be searched for.
     *
     * @param string $query The string to search for.
     * @return array
     */
    private static function _skuSearch(string $query): array
    {
        return DB::table('variants')
            ->where('sku', 'like', '%' . $query . '%')
            ->pluck('product_table_id')
            ->toArray();
    }

    /**
     * Matches product names that may be searched for.
     *
     * @param string $query The string to search for.
     * @return array
     */
    private static function _nameSearch(string $query): array
    {
        $encodedQuery = self::_customUrlEncode($query);
        return DB::table('products')
            ->where(function($execute) use($encodedQuery){
                $querySearchTerms = self::_getAllConjuctionizedSearchTerms($encodedQuery);
                foreach ($querySearchTerms as $querySearchTerm) {
                    $execute->orWhere('title', 'like', '%'.$querySearchTerm.'%');
                }
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Matches product descriptions that may be searched for.
     *
     * @param string $query The string to search for.
     * @return array
     */
    private static function _descriptionSearch(string $query): array
    {
        return DB::table('products')
            ->where('description', 'like', '%' . $query . '%')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Matches product tags that may be searched for.
     *
     * @param string $query The string to search for.
     * @return array
     */
    private static function _tagSearch(string $query): array
    {
        return DB::table('products')
            ->where('tags', 'like', '%' . $query . '%')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Retrieves Shopify data for products with the given IDs.
     *
     * @param array $ids The IDs of products to return Shopify data for.
     * @return \Illuminate\Support\Collection
     */
    private static function _shopifyDataByIds(array $ids): \Illuminate\Support\Collection
    {
        return DB::table('products')
            ->whereIn('id', $ids)
            ->pluck('shopify_data')
            ->transform(function($product) {
                return json_decode($product);
            });
    }

    /**
     * Convert query string to actually include a '+' as part of the search
     * @param string $string
     * @return string
     */
    private static function _customUrlEncode($string='') {
        $encodedString = urlencode($string);

        $entitiesMap = [
            ' ' => '%20',
            '+' => '%2B'
        ];

        $encodedPlusSigns = strpos($encodedString, '+++') !== false;
        if ($encodedPlusSigns) {
            $encodedString = str_replace(
                '+++',
                $entitiesMap[' '].$entitiesMap['+'].$entitiesMap[' '],
                $encodedString
            );
        }

        return urldecode($encodedString);
    }

    /**
     * Create an array of possible search terms - if the query string contains either of these patterns: " + ", " and ", or " & "
     * @param string $string
     * @return array
     */
    private static function _getAllConjuctionizedSearchTerms($string) {
        return collect([
            str_replace(' + ', ' and ', $string),
            str_replace(' + ', ' & ', $string),
            str_replace(' and ', ' + ', $string),
            str_replace(' and ', ' & ', $string),
            str_replace(' & ', ' + ', $string),
            str_replace(' & ', ' and ', $string)
        ])
            ->unique()
            ->values();
    }
}
