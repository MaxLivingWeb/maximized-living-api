<?php

namespace App\Http\Controllers\Reporting;

use App\Helpers\ShopifyHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RetailController extends Controller
{
    public function sales(Request $request)
    {
        try {
            $shopify = new ShopifyHelper();

            $dateObject = $this->getDateObject($request);
            $startDate = $dateObject->startDate;
            $endDate = $dateObject->endDate;
            $orders = $shopify->getAllOrders($startDate, $endDate);

            return $orders->filter(function ($value) {
                return !collect($value->note_attributes)->contains('name', 'wholesaleId');
            })->values();
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Get Start and End date from the current Request
     */
    private function getDateObject(Request $request)
    {
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

        return (Object)[
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }
}
