<?php

namespace App\Http\Controllers\Reporting;

use App\Helpers\ShopifyHelper;
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
            $shopify = new ShopifyHelper();

            $dateObject = $this->getDateObject($request);
            $startDate = $dateObject->startDate;
            $endDate = $dateObject->endDate;

            $allOrders = $shopify->getAllOrders($startDate, $endDate, request()->input('status'));
            $orders = $allOrders
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
            $shopify = new ShopifyHelper();

            $dateObject = $this->getDateObject($request);
            $startDate = $dateObject->startDate;
            $endDate = $dateObject->endDate;
            $orders = $shopify->getAllOrders($startDate, $endDate, request()->input('status'));

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
