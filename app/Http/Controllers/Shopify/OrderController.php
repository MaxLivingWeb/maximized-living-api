<?php

namespace App\Http\Controllers\Shopify;

use App\Helpers\CustomerOrderHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    /**
     * Get Shopify Orders (and exclude any test orders)
     * @param Request $request
     * @return array
     */
    public function getAllOrders(Request $request)
    {
        return CustomerOrderHelper::getAllOrdersFromRequest($request);
    }

    /**
     * Get test Orders only
     * @param Request $request
     * @return array
     */
    public function getTestOrders(Request $request)
    {
        return CustomerOrderHelper::getAllTestOrdersFromRequest($request);
    }

}
