<?php

namespace App\Http\Controllers\Reporting;

use App\UserGroup;
use App\Helpers\{ShopifyOrderHelper,UserGroupHelper};
use App\Http\Controllers\Controller;
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
            $orders = (new ShopifyOrderHelper())
                ->parseRequestData($request)
                ->getAllOrders();

            $includeUsers = (bool)$request->input('include_users');

            // Note: this will only affect data response if `include_users` is also set to true
            $includedUsersEnabledStatus = $request->input('included_users_enabled_status') ?? null;

            $affiliates = UserGroupHelper::getAllWithCommission(
                $includeUsers,
                $includedUsersEnabledStatus
            );

            return collect($affiliates)
                ->transform(function($affiliate) use($orders){
                    $affiliate->sales = collect($orders)
                        ->filter(function($order) use($affiliate) {
                            $affiliateId = collect($order->note_attributes)
                                ->where('name', 'affiliateId')
                                ->first();

                            if(is_null($affiliateId)) {
                                return false;
                            }

                            return intval($affiliateId->value) === $affiliate->id
                                || intval($affiliateId->value) === $affiliate->legacy_affiliate_id;
                        })
                        ->values();

                    return $affiliate;
                })
                ->filter(function($affiliate) {
                    return (!empty($affiliate->sales) && $affiliate->sales->isNotEmpty());
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
            $orders = (new ShopifyOrderHelper())
                ->parseRequestData($request)
                ->getAllOrders();

            $affiliate = UserGroup::with(['commission', 'location'])
                ->findOrFail($request->id);

            if ((bool)$request->input('include_users') === TRUE) {
                $affiliate->assignUsersToUserGroup();
            }

            $affiliate->sales = collect($orders)
                ->filter(function($order) use($affiliate) {
                    $affiliateId = collect($order->note_attributes)
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
