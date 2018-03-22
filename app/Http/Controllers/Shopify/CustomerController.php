<?php

namespace App\Http\Controllers\Shopify;

use App\Helpers\ShopifyHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    /**
     * Get Shopify Customers
     * @param Request $request
     * @return array
     */
    public function getCustomers(Request $request)
    {
        $shopify = new ShopifyHelper();

        $ids = $request->input('ids') !== null ? explode(',', $request->input('ids')) : null;

        return $shopify->getCustomers($ids);
    }

}
