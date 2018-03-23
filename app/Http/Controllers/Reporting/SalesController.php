<?php

namespace App\Http\Controllers\Reporting;

use App\Helpers\CustomerOrderHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function sales(Request $request)
    {
        try {
            $orders = CustomerOrderHelper::getAllOrdersFromRequest($request);
            return collect($orders)->values();
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
