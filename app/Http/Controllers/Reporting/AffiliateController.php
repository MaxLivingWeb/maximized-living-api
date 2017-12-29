<?php

namespace App\Http\Controllers\Reporting;

use App\UserGroup;
use App\Helpers\ShopifyHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AffiliateController extends Controller
{
    /**
     * Get all Affiliate Sales
     * @param Request $request
     */
    public function sales(Request $request)
    {
        try {
            $shopify = new ShopifyHelper();

            $dateObject = $this->getDateObject($request);
            $startDate = $dateObject->startDate;
            $endDate = $dateObject->endDate;
            $orders = $shopify->getAllOrders($startDate, $endDate, request()->input('status'));

            $affiliates = UserGroup::with(['commission', 'location'])->get()->where('commission', '!==', null);
            foreach ($affiliates as $affiliate) {
                $affiliate->sales = $orders
                    ->filter(function ($value) use ($affiliate) {
                        $affiliateId = collect($value->note_attributes)
                            ->where('name', 'affiliateId')
                            ->first();

                        if(is_null($affiliateId)) {
                            return false;
                        }

                        return intval($affiliateId->value) === $affiliate->id
                            || intval($affiliateId->value) === $affiliate->legacy_affiliate_id;
                    })
                    ->values();
            }

            return $affiliates->filter(function($affiliate) {
                if($affiliate->sales->isEmpty()) {
                    return false;
                }

                return true;
            })->values();
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Get sales for this Affiliate
     * @param Request $request
     */
    public function salesById(Request $request)
    {
        try {
            $shopify = new ShopifyHelper();

            $dateObject = $this->getDateObject($request);
            $startDate = $dateObject->startDate;
            $endDate = $dateObject->endDate;
            $orders = $shopify->getAllOrders($startDate, $endDate, request()->input('status'));

            $affiliate = UserGroup::with(['commission', 'location'])->findOrFail($request->id);
            $affiliate->sales = $orders
                ->filter(function ($value) use ($affiliate) {
                    $affiliateId = collect($value->note_attributes)
                        ->where('name', 'affiliateId')
                        ->first();

                    if(is_null($affiliateId)) {
                        return false;
                    }

                    return intval($affiliateId->value) === $affiliate->id
                        || intval($affiliateId->value) === $affiliate->legacy_affiliate_id;
                })
                ->values();

                return response()->json($affiliate);
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
