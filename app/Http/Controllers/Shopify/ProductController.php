<?php

namespace App\Http\Controllers\Shopify;

use App\Helpers\ShopifyHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    /**
     * Get Shopify Products
     * @param Request $request
     * @return array
     */
    public function getProducts(Request $request)
    {
        $shopify = new ShopifyHelper();

        if (!empty($request->audience_types)) {
            return $this->getProductsFromAudienceTypes($request->audience_types);
        }

        return $shopify->getProducts();
    }

    /**
     * Get ALL unique Audience Types that are applied on products from Shopify settings
     * @param Request $request
     */
    public function getAllProductsAudienceTypes(Request $request)
    {
        $shopify = new ShopifyHelper();

        $products = $shopify->getProducts();

        // Returns a list of all product audience types
        // ie - "Client, VIP, Admin"
        $audienceTypeCombinations = collect($products)
            ->transform(function($product){
                return $this->getAudienceTypeCombinationsForProduct($product);
            })
            ->flatten()
            ->all();

        // Returns all audience types as a single value, that are all unique
        $audienceTypes = array_values($this->getUniqueAudienceTypesFromAllCombinations($audienceTypeCombinations));
        
        return $audienceTypes;
    }

    /**
     * Get Shopify Products that have these Audience Type values
     * @param string $audienceTypes
     */
    private function getProductsFromAudienceTypes(string $audienceTypes = '')
    {
        $shopify = new ShopifyHelper();

        if ($audienceTypes === '') {
            return;
        }

        $selectedAudienceTypes = explode(',',$audienceTypes);
        $products = $shopify->getProducts();

        $filteredProducts = collect($products)
            ->filter(function($product) use($selectedAudienceTypes){
                $audienceTypesForProduct = $this->getUniqueAudienceTypesFromAllCombinations(
                    $this->getAudienceTypeCombinationsForProduct($product)
                );

                $match = collect($selectedAudienceTypes)
                    ->filter(function($audienceType) use($audienceTypesForProduct){
                        return collect($audienceTypesForProduct)->contains($audienceType);
                    })
                    ->isNotEmpty();

                return $match;
            })
            ->all();

        return $filteredProducts;
    }

    /**
     * Get all audience types as a flat array that are all unique
     * @param $audienceTypeCombinations
     * @return mixed
     */
    private function getUniqueAudienceTypesFromAllCombinations($audienceTypeCombinations)
    {
        return collect($audienceTypeCombinations)
            ->transform(function($audienceTypeCombination){
                return explode(', ', $audienceTypeCombination);
            })
            ->flatten()
            ->unique()
            ->all();
    }

    /**
     * Get all audience type combinations for this product
     * @param $product
     * @return array
     */
    private function getAudienceTypeCombinationsForProduct($product)
    {
        $audienceTypesForProduct = collect($product->options)
            ->where('name', 'Audience')
            ->pluck('values')
            ->unique()
            ->first();

        return collect($audienceTypesForProduct)
            ->transform(function($audienceType){
                return str_replace('/', ', ', $audienceType);
            })
            ->all();
    }
}
