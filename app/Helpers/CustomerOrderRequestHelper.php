<?php

namespace App\Helpers;

use App\Helpers\{DateRequestHelper, ShopifyHelper};
use Illuminate\Http\Request;

class CustomerOrderRequestHelper
{
    /**
     * Get All Shopify Orders - based on provided request paramters
     */
    public static function getAllOrders(Request $request)
    {
        $shopify = new ShopifyHelper();

        $dateObject = DateRequestHelper::getDateObject($request);
        $startDate = $dateObject->startDate;
        $endDate = $dateObject->endDate;

        return $shopify->getAllOrders($startDate, $endDate, request()->input('status'));
    }
}
