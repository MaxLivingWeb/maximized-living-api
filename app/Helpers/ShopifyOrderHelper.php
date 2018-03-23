<?php

namespace App\Helpers;

use App\Helpers\{DateRequestHelper, ShopifyHelper};
use Illuminate\Http\Request;

class ShopifyOrderHelper
{
    /**
     * Default setting for filtering order results (remove test orders)
     * @var bool
     */
    private $excludeTestOrdersByDefault = true;

    /**
     * Default settings for updating sale calculations on order results
     * @var bool
     */
    private $recalculateSubtotalsByDefault = true;

    /**
     * Start Date for retrieving orders
     * @var null|Carbon
     */
    private $startDate = null;

    /**
     * End Date for retrieving orders
     * @var null|Carbon
     */
    private $endDate = null;

    /**
     * Shopify Order status to identify which types of orders to retrieve.
     * @var null|string ('open' (default), 'closed', 'cancelled', 'any')
     */
    private $orderStatus = null;

    /**
     * Current setting for filtering order results. If null, will default  to the $excludeTestOrdersByDefault value
     * @var null|bool
     */
    private $excludeTestOrders = null;

    /**
     * Current setting for filtering order results. If null, will default  to the $recalculateSubtotalsByDefault value
     * @var null|bool
     */
    private $recalculateSubtotals = null;

    /**
     * Parse Request Data which will then be used to get Shopify Orders
     * @param Request $request
     * @return $this
     */
    public function parseRequestData(Request $request)
    {
        $dateObject = DateRequestHelper::getDateObject($request);
        $this->startDate = $dateObject->startDate;
        $this->endDate = $dateObject->endDate;

        $this->orderStatus = request()->input('status');

        $this->excludeTestOrders = !empty($request->input('excludeTestOrders'))
            ? (bool)$request->input('excludeTestOrders')
            : $this->excludeTestOrdersByDefault;

        $this->recalculateSubtotals = !empty($request->input('recalculateSubtotals'))
            ? (bool)$request->input('recalculateSubtotals')
            : $this->recalculateSubtotalsByDefault;

        return $this;
    }

    /**
     * Get All Shopify Orders
     * @method static
     * @param null|Carbon $startDate
     * @param null|Carbon $endDate
     * @param null|string $orderStatus
     * @param null|bool $excludeTestOrders
     * @param null|bool $recalculateSubtotals
     * @return array
     */
    public function getAllOrders(
        $startDate = null,
        $endDate = null,
        $orderStatus = null,
        $excludeTestOrders = null,
        $recalculateSubtotals = null
    ){
        $shopify = new ShopifyHelper();

        $startDate = $startDate ?? $this->startDate;
        $endDate = $endDate ?? $this->endDate;
        $orderStatus = $orderStatus ?? $this->orderStatus;
        $excludeTestOrders = $excludeTestOrders ?? $this->excludeTestOrders ?? $this->excludeTestOrdersByDefault;
        $recalculateSubtotals = $recalculateSubtotals ?? $this->recalculateSubtotals ?? $this->recalculateSubtotalsByDefault;

        $orders = $shopify->getAllOrders($startDate, $endDate, $orderStatus);

        if ($excludeTestOrders === true) {
            $orders = $this->excludeTestOrders($orders);
        }

        if ($recalculateSubtotals === true) {
            $orders = $this->calculateSubtotalForRefundedOrders($orders);
        }

        return $orders;
    }

    /**
     * Exclude Arcane Developer test Orders from being returned in results
     * @method static
     * @param array $orders
     * @return array
     */
    public function excludeTestOrders(array $orders)
    {
        return collect($orders)
            ->filter(function($order){
                return !$this->isTestOrder($order);
            })
            ->all();
    }

    /**
     * Validate email associated with Shopify Order to verify this is a legit order
     * @param $order
     * @return bool
     */
    private function isTestOrder($order)
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
    public function calculateSubtotalForRefundedOrders(array $orders)
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
                $finalSubtotal = abs($order->subtotal_price - $refundedSubtotalAmount);

                // 2. Grand Total after taxes, discounts, everything
                // 2.a Refunded Amount (Total)
                $refundedTotalAmount = collect($order->refunds)
                    ->sum(function($refund){
                        return collect($refund->transactions)->sum('amount');
                    });

                // 2.b Final Total spent on everything
                $finalTotal = abs($order->total_price - $refundedTotalAmount);

                $order->subtotal_refunded_amount = number_format($refundedSubtotalAmount,2, '.', '');
                $order->subtotal_price_final = number_format($finalSubtotal, 2, '.', '');
                $order->total_refunded_amount = number_format($refundedTotalAmount,2, '.', '');
                $order->total_price_final = number_format($finalTotal,2, '.', '');

                // Refund message
                $refundMessage = [];
                foreach ($order->refunds as $refund) {
                    $defaultRefundMessage = 'No note added.';
                    if (count($refund->refund_line_items) === 1) {
                        $defaultRefundMessage = 'Refunded item.';
                    }
                    elseif (count($refund->refund_line_items) > 1) {
                        $defaultRefundMessage = 'Refunded items.';
                    }
                    $refundMessage[] = !empty($refund->note) ? $refund->note : $defaultRefundMessage;
                }
                $refundMessage = (count($refundMessage) > 0) ? implode(', ', $refundMessage) : null;
                $order->refund_note = $refundMessage;

                // Modify Line Items to include "refunded" boolean property
                $order->line_items = collect($order->line_items)
                    ->transform(function($item) use($order){
                        $item->refunded = collect($order->refunds)
                            ->filter(function($refund) use($item){
                                return collect($refund->refund_line_items)
                                    ->filter(function($refund_line_item) use($item){
                                        return $item->id === $refund_line_item->line_item->id;
                                    })
                                    ->isNotEmpty();
                            })
                            ->isNotEmpty();
                        return $item;
                    })
                    ->all();

                return $order;
            })
            ->all();
    }
}
