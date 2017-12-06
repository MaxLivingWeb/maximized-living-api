<?php

namespace App\Http\Controllers;

use App\Helpers\ShopifyHelper;
use App\UserGroup;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function salesById(Request $request)
    {
        try {
            $userGroup = UserGroup::findOrFail($request->id);

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

            $orders = $shopify->getAllOrders($startDate, $endDate);

            $orders = $orders->filter(function ($value) use ($userGroup) {
                $affiliateId = collect($value->note_attributes)->where('name', 'affiliateId')->first();

                if(is_null($affiliateId)) {
                    return false;
                }

                //TODO: swap legacy_affiliate_id for affiliate_id
                return intval($affiliateId->value) === $userGroup->legacy_affiliate_id;
            })->values();

            return $orders;
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
