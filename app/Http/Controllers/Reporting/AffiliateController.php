<?php

namespace App\Http\Controllers\Reporting;

use App\UserGroup;
use App\Helpers\CustomerOrderRequestHelper;
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
            $orders = CustomerOrderRequestHelper::getAllOrders($request);

            $affiliates = UserGroup::with(['commission', 'location'])
                ->get()
                ->where('commission', '!==', null);

            foreach ($affiliates as $affiliate) {
                $affiliate->sales = collect($orders)
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

            return $affiliates
                ->filter(function($affiliate) {
                    return $affiliate->sales->isNotEmpty();
                })
                ->values();
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
            $orders = CustomerOrderRequestHelper::getAllOrders($request);

            $affiliate = UserGroup::with(['commission', 'location'])
                ->findOrFail($request->id);

            $affiliate->sales = collect($orders)
                ->filter(function ($value) use ($affiliate) {
                    $affiliateId = collect($value->note_attributes)
                        ->where('name', 'affiliateId')
                        ->first();

                    if (is_null($affiliateId)) {
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

}
