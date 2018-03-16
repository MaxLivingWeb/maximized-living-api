<?php

namespace App\Http\Controllers\Reporting;

use App\Helpers\CustomerOrderRequestHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function sales(Request $request)
    {
        try {
            return CustomerOrderRequestHelper::getAllOrders($request);
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
