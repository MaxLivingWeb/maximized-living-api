<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class SearchHelper
{
    public static function productSearch(string $query): array
    {
        $skuSearch = self::skuSearch($query);
        $nameSearch = self::nameSearch($query);
        $descriptionSearch = self::descriptionSearch($query);
        
        $results = array_merge($skuSearch, $nameSearch, $descriptionSearch);
        
        $serialized = array_map('serialize', $results);
        $unique = array_unique($serialized);
        $search = array_intersect_key($results, $unique);
    
        return $search;
    }
    
    private static function skuSearch(string $query): array
    {
        $results = DB::table('variants')
            ->where('sku', 'like', '%' . $query . '%')
            ->pluck('productTableID')
            ->toArray();
        
        $uniqueVariants = array_unique($results);
        
        foreach ($uniqueVariants as $uniqueVariant) {
            $products[] = self::getProductsById($uniqueVariant);
        }
        
        if (isset($products)) {
            return $products;
        }
        
        return [];
    }
    
    private static function nameSearch(string $query): array
    {
        $results = DB::table('products')
            ->where('title', 'like', '%' . $query . '%')
            ->pluck('id')
            ->toArray();
        
        $uniqueProducts = array_unique($results);
        
        foreach ($uniqueProducts as $uniqueProduct) {
            $products[] = self::getProductsById($uniqueProduct);
        }
        
        if (isset($products)) {
            return $products;
        }
        
        return [];
    }
    
    private static function descriptionSearch(string $query): array
    {
        $results = DB::table('products')
            ->where('description', 'like', '%' . $query . '%')
            ->pluck('id')
            ->toArray();
        
        $uniqueProducts = array_unique($results);
        
        foreach ($uniqueProducts as $uniqueProduct) {
            $products[] = self::getProductsById($uniqueProduct);
        }
        
        if (isset($products)) {
            return $products;
        }
        
        return [];
    }
    
    private static function getProductsById(int $id)
    {
        $product = DB::table('products')
            ->where('id', $id)
            ->first();
        
        $product->variants = DB::table('variants')
            ->where('productTableID', $id)
            ->orderBy('position')
            ->get()
            ->toArray();
        
        return $product;
    }
}
