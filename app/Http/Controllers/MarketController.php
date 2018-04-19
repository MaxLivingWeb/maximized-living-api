<?php

namespace App\Http\Controllers;

use App\Market;
use App\MarketSubscriptionCount;
use Carbon\Carbon;
use InvalidArgumentException;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    /**
     * Get ALL Markets
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return Market::all();
    }

    /**
     * Retrieves a single Market
     *
     * @param integer $id The ID of the Market
     * @return array
     */
    public function getById($id)
    {
        return Market::findOrFail($id);
    }

    /**
     * Get subscription count for a market
     * @param integer $id The ID of the Market
     * @param Request $request The Request object
     * @return object or array
     */
    public function getSubscriptionCount($id, Request $request)
    {
        $query = MarketSubscriptionCount::where('market_id', $id);
        if ($request->query('start_date') && $request->query('end_date')) {
            $format = 'Y-m-d';
            try {
                $startDate = Carbon::createFromFormat($format, $request->query('start_date'))
                    ->hour(0)
                    ->minute(0)
                    ->second(0);
            } catch (InvalidArgumentException $e) {
                return response('Bad start_date format:' . $e->getMessage(), 400);
            }

            try {
                $endDate = Carbon::createFromFormat($format, $request->query('end_date'))
                    ->hour(0)
                    ->minute(0)
                    ->second(0);
            } catch (InvalidArgumentException $e) {
                return response('Bad end_date format:' . $e->getMessage(), 400);
            }

            $query = $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $query = $query->orderBy('created_at', 'DESC');

        if($request->query('latest_only') === 'true') {
            return $query->first();
        }

        return $query->get();
    }
}
