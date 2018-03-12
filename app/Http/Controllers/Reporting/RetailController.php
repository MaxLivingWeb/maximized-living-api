<?php

namespace App\Http\Controllers\Reporting;

use App\Helpers\CognitoHelper;
use App\Helpers\CustomerOrderRequestHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RetailController extends Controller
{
    /**
     * Get all Sales for Retail Customers
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerSales(Request $request)
    {
        try {
            $cognito = new CognitoHelper();

            $orders = CustomerOrderRequestHelper::getAllOrders($request);

            $affiliateEmails = collect($cognito->listUsers())
                ->pluck('email')
                ->unique()
                ->toArray();

            return collect($orders)
                ->filter(function($order){
                    return $order->source_name !== 'pos';
                })
                ->each(function($order){
                    $order->email = strtolower($order->email);
                })
                ->whereNotIn('email', $affiliateEmails)
                ->values()
                ->all();
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function posSales(Request $request)
    {
        try {
            $orders = CustomerOrderRequestHelper::getAllOrders($request);

            return collect($orders)
                ->where('source_name', 'pos')
                ->values();
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

}
