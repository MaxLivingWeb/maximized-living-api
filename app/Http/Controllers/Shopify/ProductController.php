<?php

namespace App\Http\Controllers\Shopify;

use App\Helpers\ProductAudienceTypeHelper;
use App\Helpers\ProductImportHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    /**
     * Get Shopify Products
     * @param Request $request
     * @return array
     */


    /**
     * Get ALL unique Audience Types that are applied on products from Shopify settings
     * @param Request $request
     * @return array
     */
    public function getAllProductsAudienceTypes()
    {
        return ProductAudienceTypeHelper::getAllProductsAudienceTypes();
    }

    /**
     * Import Shopify Products to Database
     * @param Request $request
     * @return void
     */
    public function importProductsToDatabase(Request $request)
    {
        $products = $this->getProducts($request);

        $productImporter = new ProductImportHelper();
        $productImporter->importProducts($products);
    }

}
