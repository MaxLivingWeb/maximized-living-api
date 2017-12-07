<?php

namespace App\Http\Controllers;

use App\Helpers\ShopifyHelper;
use App\UserGroup;
use Illuminate\Http\Request;

class AffiliateController extends Controller
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

            $orders = $shopify->getAllOrders($startDate, $endDate);

            $affiliates = UserGroup::with(['commission', 'location'])->get()->where('commission', '!==', null);

            foreach ($affiliates as $affiliate) {
                $affiliate->sales = $orders->filter(function ($value) use ($affiliate) {
                    $affiliateId = collect($value->note_attributes)->where('name', 'affiliateId')->first();

                    if(is_null($affiliateId)) {
                        return false;
                    }

                    return intval($affiliateId->value) === $affiliate->id;
                })->values();
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
}
