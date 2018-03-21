<?php

namespace App\Http\Controllers\Reporting;

use App\Helpers\CustomerOrderHelper;
use App\Http\Controllers\Controller;
use App\UserGroup;
use Illuminate\Http\Request;

class WholesaleController extends Controller
{
    /**
     * Return all wholesaler sales, grouped by wholesaler.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|\Illuminate\Http\JsonResponse
     */
    public function sales(Request $request)
    {
        try {
            $orders = collect(CustomerOrderHelper::getAllOrdersFromRequest($request))
                ->filter(function($value) {
                    return !is_null(
                        collect($value->note_attributes)
                            ->where('name', 'wholesaleId')
                            ->first()
                    );
                })
                ->groupBy(function($value) {
                    return collect($value->note_attributes)
                        ->where('name', 'wholesaleId')
                        ->first()
                        ->value;
                });

            $affiliates = [];
            foreach($orders as $groupId => $groupOrders) {
                $affiliate = UserGroup::with(['commission', 'location'])->find($groupId);
                if(!empty($affiliate)){
                    $affiliate->sales = $groupOrders;
                    $affiliates[] = $affiliate;
                }
            }

            return $affiliates;
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function salesById(Request $request)
    {
        try {
            $orders = CustomerOrderHelper::getAllOrdersFromRequest($request);

            $affiliate = UserGroup::with(['commission', 'location'])->findOrFail($request->id);

            $affiliate->sales = collect($orders)
                ->filter(function ($value) use ($affiliate) {
                    $wholesaleId = collect($value->note_attributes)
                        ->where('name', 'wholesaleId')
                        ->first();

                    if(is_null($wholesaleId)) {
                        return false;
                    }

                    return intval($wholesaleId->value) === $affiliate->id;
                })
                ->values();

            return $affiliate;
        }
        catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

}
