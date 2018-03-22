<?php

namespace App\Helpers;

use App\Helpers\{DateRequestHelper, ShopifyHelper};
use Illuminate\Http\Request;

class CustomerOrderHelper
{
    /**
     * Default setting for filtering order results (remove test orders)
     * @var bool
     */
    private static $excludeTestOrdersByDefault = true;

    /**
     * Default settings for updating sale calculations on order results
     * @var bool
     */
    private static $calculateSalesByDefault = true;

    /**
     * Get All Shopify Orders - based on provided request parameters
     * @method static
     * @param Request $request
     * @return array
     */
    public static function getAllOrdersFromRequest(Request $request){
        $shopify = new ShopifyHelper();

        $dateObject = DateRequestHelper::getDateObject($request);
        $startDate = $dateObject->startDate;
        $endDate = $dateObject->endDate;

        $orders = $shopify->getAllOrders($startDate, $endDate, request()->input('status'));

        $excludeTestOrders = (
            !empty($request->input('excludeTestOrders'))
                ? (bool)$request->input('excludeTestOrders')
                : self::$excludeTestOrdersByDefault
        );
        if ($excludeTestOrders === true) {
            $orders = self::excludeTestOrders($orders);
        }

        $calculateSales = (
            !empty($request->input('calculateSales'))
                ? (bool)$request->input('calculateSales')
                : self::$calculateSalesByDefault
        );
        if ($calculateSales === true) {
            $orders = self::calculateSubtotalForRefundedOrders($orders);
        }

        return $orders;
    }

    /**
     * Get ALL Shopify Test Orders
     * @param Request $request
     * @return array
     */
    public static function getAllTestOrdersFromRequest(Request $request)
    {
        $shopify = new ShopifyHelper();

        $dateObject = DateRequestHelper::getDateObject($request);
        $startDate = $dateObject->startDate;
        $endDate = $dateObject->endDate;

        $orders = $shopify->getAllOrders($startDate, $endDate, request()->input('status'));

        $calculateSales = (
            !empty($request->input('calculateSales'))
                ? (bool)$request->input('calculateSales')
                : self::$calculateSalesByDefault
        );
        if ($calculateSales === true) {
            $orders = self::calculateSubtotalForRefundedOrders($orders);
        }

        return self::filterTestOrders($orders);
    }

    /**
     * Exclude Arcane Developer test Orders from being returned in results
     * @method static
     * @param array $orders
     * @return array
     */
    public static function excludeTestOrders(array $orders)
    {
        return collect($orders)
            ->filter(function($order){
                return !self::isTestOrder($order);
            })
            ->all();
    }

    /**
     * Filter orders to only get Arcane Developer test orders
     * @method static
     * @param array $orders
     * @return array
     */
    public static function filterTestOrders(array $orders)
    {
        return collect($orders)
            ->filter(function($order){
                return self::isTestOrder($order);
            })
            ->all();
    }

    /**
     * Validate Order to see if email used is legit
     * @param $order
     * @return bool
     */
    private static function isTestOrder($order)
    {
        $email = $order->customer->email ?? $order->email ?? null;

        // Must be a test order, since there isn't an email address
        if (empty($email)) {
            return false;
        }

        // Check if email was an Arcane Dev email
        return (
            strpos($email, '@arcane.ws') !== false
            && strpos($email, 'dev') !== false
        );
    }

    /**
     * Calculate Subtotal on refunded/partially_refunded orders
     * @method static
     * @param array $orders
     * @return array
     */
    public static function calculateSubtotalForRefundedOrders(array $orders)
    {
        return collect($orders)
            ->transform(function($order){
                // 1. Subtotal spent on items
                // 1.a Refunded Amount (Subtotal spent on items)
                $refundedSubtotalAmount = collect($order->refunds)
                    ->sum(function($refund){
                        return collect($refund->refund_line_items)->sum('subtotal');
                    });

                // 1.b Final Subtotal spent on Items
                $finalSubtotal = $order->subtotal_price - $refundedSubtotalAmount;

                // 2. Grand Total after taxes, discounts, everything
                // 2.a Refunded Amount (Total)
                $refundedTotalAmount = collect($order->refunds)
                    ->sum(function($refund){
                        return collect($refund->transactions)->sum('amount');
                    });

                // 2.b Final Total spent on everything
                $finalTotal = $order->total_price - $refundedTotalAmount;

                $order->subtotal_refunded_amount = number_format($refundedSubtotalAmount,2);
                $order->subtotal_price_final = number_format($finalSubtotal, 2, '.', '');
                $order->total_refunded_amount = number_format($refundedTotalAmount,2, '.', '');
                $order->total_price_final = number_format($finalTotal,2, '.', '');

                return $order;
            })
            ->all();
    }
}
