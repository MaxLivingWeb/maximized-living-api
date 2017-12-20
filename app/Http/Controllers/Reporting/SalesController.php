<?php

namespace App\Http\Controllers;

use App\Helpers\ShopifyHelper;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function sales(Request $request)
    {
        try {
            $shopify = new ShopifyHelper();

            $startDate = null;
            $endDate = null;
            if(request()->has('startDate') || request()->has('endDate')) {
                $request->validate([
                    'startDate' => 'required|date',
                    'endDate'   => 'required|date'
                ]);

                $startDate = new \DateTime(request()->input('startDate'));
                $endDate = new \DateTime(request()->input('endDate'));
            }

            return $shopify->getAllOrders($startDate, $endDate);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
