<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class ProductHelper
{
    private $databaseProducts;
    
    public function __construct()
    {
        $this->databaseProducts = $this->getDatabaseProducts();
    }
    
    /**
     * Sends an error notification to the ecomm team
     *
     * @param array $products
     */
    public function importProducts(array $products): void
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
            
            $productTableID = $this->updateOrInsert($data);
            
            unset($this->databaseProducts[$productTableID]);
            
            self::saveVariants(
                $productTableID,
                $product['variants'],
                2,
                1
            );
        }
        $this->deleteOldProducts();
    }
    
    /**
     * Grabs all products from the database.
     *
     * @return array
     */
    private function getDatabaseProducts(): array
    {
        $products = DB::table('products')->pluck('productId', 'id');
        
        return $products->toArray();
    }
    
    /**
     * Determines if a product exists and therefore should be
     * updated or if it needs to be created.
     *
     * @param array $product
     * @return int
     */
    private function updateOrInsert(array $product): int
    {
        $rowId = array_search($product['productId'], $this->databaseProducts, false);
        
        if ($rowId !== false) {
            self::updateProduct($product, $rowId);
            
            return $rowId;
        }
    
        return self::saveProduct($product);
    
    }
    
    /**
     * Updates the product if it exists in the database.
     *
     * @param array $product
     * @param int   $row
     */
    private static function updateProduct(array $product, int $row): void
    {
        DB::table('products')
            ->where('id', $row)
            ->update(
                [
                    'productId'   => $product['productId'],
                    'title'       => $product['title'],
                    'description' => $product['description'],
                    'vendor'      => $product['vendor'],
                    'productType' => $product['productType'],
                    'handle'      => $product['handle'],
                    'tags'        => $product['tags'],
                    'updated_at'  => \Carbon\Carbon::now()
                ]
            );
    }
    
    /**
     * Saves products to the database and returns their
     * id. Returns -1 if the product doesn't save.
     *
     * @param array $product
     * @return int
     */
    private static function saveProduct(array $product): int
    {
        try {
            $result = DB::table('products')->insertGetId(
                [
                    'productId'   => $product['productId'],
                    'title'       => $product['title'],
                    'description' => $product['description'],
                    'vendor'      => $product['vendor'],
                    'productType' => $product['productType'],
                    'handle'      => $product['handle'],
                    'tags'        => $product['tags'],
                    'created_at'  => \Carbon\Carbon::now(),
                    'updated_at'  => \Carbon\Carbon::now()
                ]
            );
        }
        catch (Exception $e) {
            return -1;
        }
        
        return $result;
    }
    
    /**
     * Saves all product variants but deletes them first. This prevents
     * any duplicates from occurring.
     *
     * @param int   $productTableID
     * @param array $variants
     * @param int   $userTypePosition
     * @param int   $variantNamePosition
     */
    private static function saveVariants(
        int $productTableID,
        array $variants,
        int $userTypePosition,
        int $variantNamePosition
    ): void
    {
        DB::table('variants')
            ->where('productTableID', $productTableID)
            ->delete();
        foreach ($variants as $variant) {
            DB::table('variants')->insert(
                [
                    'variantID'        => $variant['id'],
                    'productTableID'   => $productTableID,
                    'productID'        => $variant['product_id'],
                    'title'            => $variant['title'],
                    'sku'              => $variant['sku'],
                    'price'            => $variant['price'],
                    'compareAtPrice'   => $variant['compare_at_price'],
                    'userType'         => $variant['option' . $userTypePosition],
                    'variantName'      => $variant['option' . $variantNamePosition],
                    'qty'              => $variant['inventory_quantity'],
                    'position'         => $variant['position'],
                    'weight'           => $variant['weight'],
                    'weightUnit'       => $variant['weight_unit'],
                    'requiresShipping' => $variant['requires_shipping'],
                    'grams'            => $variant['grams'],
                    'taxable'          => $variant['taxable'],
                    'created_at'       => \Carbon\Carbon::now(),
                    'updated_at'       => \Carbon\Carbon::now()
                ]
            );
        }
    
    }
    
    /**
     * Deletes any products that were in the database but no longer
     * exist in the Shopify system.
     */
    private function deleteOldProducts(): void
    {
        foreach ($this->databaseProducts as $key => $product) {
            DB::table('products')
                ->where('id', $key)
                ->delete();
        }
    }
}
