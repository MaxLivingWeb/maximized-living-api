<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class ProductImportHelper
{
    private $databaseProducts;
    
    public function __construct()
    {
        $this->databaseProducts = $this->getDatabaseProducts();
    }

    /**
     * Imports products from Shopify
     *
     * @param array $products
     * @return void
     */
    public function importProducts(array $products = [])
    {
        if (empty($products)) {
            Log::error('Failed to Import Products to Database. No Products to Import.');
            die('No Products to Import');
        }

        try {
            foreach ($products as $product) {
                $data = [
                    'product_id'   => $product->id,
                    'title'        => $product->title,
                    'description'  => $product->body_html,
                    'image'        => $product->image->src ?? '',
                    'vendor'       => $product->vendor ?? '',
                    'product_type' => $product->product_type,
                    'handle'       => $product->handle,
                    'tags'         => $product->tags,
                    'shopify_data' => json_encode($product),
                ];

                $productTableID = $this->updateOrInsert($data);

                unset($this->databaseProducts[$productTableID]);

                self::saveVariants(
                    $productTableID,
                    $product->variants,
                    2,
                    1
                );
            }

            $this->deleteOldProducts();
        }
        catch (\Exception $e) {
            Log::error($e);
            return response()->json(
                $e->getMessage(),
                500
            );
        }
    }
    
    /**
     * Grabs all products from the database.
     *
     * @return array
     */
    private function getDatabaseProducts(): array
    {
        $products = DB::table('products')->pluck('product_id', 'id');
        
        return $products->toArray();
    }
    
    /**
     * Determines if a product exists and therefore should be updated or if it needs to be created.
     *
     * @param array $product
     * @return int
     */
    private function updateOrInsert(array $product): int
    {
        $rowId = array_search($product['product_id'], $this->databaseProducts, false);
        
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
     * @return void
     */
    private static function updateProduct(array $product, int $row): void
    {
        DB::table('products')
            ->where('id', $row)
            ->update(
                [
                    'product_id'   => $product['product_id'],
                    'title'        => $product['title'],
                    'description'  => $product['description'],
                    'image'        => $product['image'],
                    'vendor'       => $product['vendor'],
                    'product_type' => $product['product_type'],
                    'handle'       => $product['handle'],
                    'tags'         => $product['tags'],
                    'shopify_data' => $product['shopify_data'],
                    'updated_at'   => \Carbon\Carbon::now()
                ]
            );
    }
    
    /**
     * Saves products to the database and returns their id. Returns -1 if the product doesn't save.
     *
     * @param array $product
     * @return int
     */
    private static function saveProduct(array $product): int
    {
        try {
            $result = DB::table('products')->insertGetId(
                [
                    'product_id'   => $product['product_id'],
                    'title'        => $product['title'],
                    'description'  => $product['description'],
                    'image'        => $product['image'],
                    'vendor'       => $product['vendor'],
                    'product_type' => $product['product_type'],
                    'handle'       => $product['handle'],
                    'tags'         => $product['tags'],
                    'shopify_data' => $product['shopify_data'],
                    'created_at'   => \Carbon\Carbon::now(),
                    'updated_at'   => \Carbon\Carbon::now()
                ]
            );
        }
        catch (Exception $e) {
            return -1;
        }
        
        return $result;
    }
    
    /**
     * Saves all product variants but deletes them first. This prevents any duplicates from occurring.
     *
     * @param int   $productTableID
     * @param array $variants
     * @param int   $userTypePosition
     * @param int   $variantNamePosition
     * @return void
     */
    private static function saveVariants(
        int $productTableID,
        array $variants,
        int $userTypePosition,
        int $variantNamePosition
    ): void
    {
        DB::table('variants')
            ->where('product_table_id', $productTableID)
            ->delete();
        
        foreach ($variants as $variant) {
            DB::table('variants')->insert(
                [
                    'variant_id'         => $variant->id,
                    'product_table_id'   => $productTableID,
                    'product_id'         => $variant->product_id,
                    'title'              => $variant->title,
                    'sku'                => $variant->sku,
                    'price'              => $variant->price,
                    'compare_at_price'   => $variant->compare_at_price ?? '',
                    'user_type'          => $variant->{'option' . $userTypePosition},
                    'variant_name'       => $variant->{'option' . $variantNamePosition},
                    'qty'                => $variant->inventory_quantity,
                    'position'           => $variant->position,
                    'weight'             => $variant->weight,
                    'weight_unit'        => $variant->weight_unit,
                    'requires_shipping'  => $variant->requires_shipping,
                    'grams'              => $variant->grams,
                    'taxable'            => $variant->taxable,
                    'created_at'         => \Carbon\Carbon::now(),
                    'updated_at'         => \Carbon\Carbon::now()
                ]
            );
        }
    
    }
    
    /**
     * Deletes any products that were in the database but no longer exist in the Shopify system.
     * @return void
     */
    private function deleteOldProducts(): void
    {
        DB::table('products')
            ->whereIn('id', array_keys($this->databaseProducts))
            ->delete();
    }
}
