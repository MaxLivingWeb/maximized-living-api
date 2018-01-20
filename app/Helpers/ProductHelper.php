<?php

namespace App\Helpers;

class ProductHelper
{
    /**
     * Sends an error notification to the ecomm team
     *
     * @param array $products
     */
    public static function importProducts(array $products)
    {
        foreach ($products as $key => $product) {
            $data = [
                'productId'   => $product['id'],
                'title'       => $product['title'],
                'description' => $product['body_html'],
                'vendor'      => $product['vendor'],
                'productType' => $product['product_type'],
                'handle'      => $product['handle'],
                'tags'        => $product['tags'],
            ];
            
            echo 'here';
            self::saveProduct($data);
            die('here');
        }
        
        dd('import', $products);
    }
    
    private static function saveProduct(array $product)
    {
        dump($product);
    }
}
