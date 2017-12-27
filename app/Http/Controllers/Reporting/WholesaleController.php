<?php

namespace App\Http\Controllers\Reporting;

use App\Helpers\ShopifyHelper;
use App\Http\Controllers\Controller;
use App\UserGroup;
use Illuminate\Http\Request;

class WholesaleController extends Controller
{
    public function sales(Request $request)
    {
        try {
            $shopify = new ShopifyHelper();

            $dateObject = $this->getDateObject($request);
            $startDate = $dateObject->startDate;
            $endDate = $dateObject->endDate;
            $orders = $shopify->getAllOrders($startDate, $endDate);

            $affiliate = UserGroup::with(['commission', 'location'])->findOrFail($request->id);

            $affiliate->sales = $orders->filter(function ($value) use ($affiliate) {
                $wholesaleId = collect($value->note_attributes)->where('name', 'wholesaleId')->first();

                if(is_null($wholesaleId)) {
                    return false;
                }

                return intval($wholesaleId->value) === $affiliate->id;
            })->values();

            return $affiliate;
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
