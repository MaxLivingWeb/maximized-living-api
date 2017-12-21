<?php

namespace App\Http\Controllers;

use App\UserGroup;
use App\Helpers\ShopifyHelper;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;

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

            $startDate = $this->getDates($request)->startDate;
            $endDate = $this->getDates($request)->endDate;
            $orders = $shopify->getAllOrders($startDate, $endDate);

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

                        return intval($affiliateId->value) === $affiliate->id || intval($affiliateId->value) === $affiliate->legacy_affiliate_id;
                    })
                    ->values();
            }

            return $affiliates->filter(function($affiliate) {
                if($affiliate->sales->isEmpty()) {
                    return false;
                }

                return true;
            });
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
            $userController = new UserController();

            $startDate = $this->getDates($request)->startDate;
            $endDate = $this->getDates($request)->endDate;
            $orders = $shopify->getAllOrders($startDate, $endDate);

            $affiliateUser = $userController->getUser($request->id)->getData();
            $affiliate = UserGroup::with(['commission', 'location'])->findOrFail($affiliateUser->affiliate->id);
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
            return $e;
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Get Start and End date from the current Request
     */
    private function getDates(Request $request)
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
