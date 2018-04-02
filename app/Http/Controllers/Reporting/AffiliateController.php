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
            $includedUsersEnabledStatus = $request->input('included_users_enabled_status') ?? null; //Note: Can only be assigned if `include_users` is also set to true.
            $affiliates = UserGroupHelper::getAllWithCommission(
                $includeUsers,
                $includedUsersEnabledStatus
            );

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

            return collect($affiliates)
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
