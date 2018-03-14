<?php

namespace App\Http\Controllers;

use App\Region;
use App\RegionalSubscriptionCount;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Get ALL Regions
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return Region::all();
    }

    /**
     * Retrieves a single Region
     *
     * @param integer $id The ID of the Region
     * @return array
     */
    public function getById($id)
    {
        return Region::findOrFail($id);
    }

    /**
     * Get subscription count for a region
     * @param integer $id The ID of the Region
     * @param Request $request The Request object
     * @return object or array
     */
    public function getSubscriptionCount($id, Request $request)
    {
        $query = RegionalSubscriptionCount::where('region_id', $id);
        if ($request->query('start_date') && $request->query('end_date')) {
            $format = 'd-m-Y';
            $startDate = Carbon::createFromFormat($format, $request->query('start_date'))
                ->hour(0)
                ->minute(0)
                ->second(0);
            $endDate = Carbon::createFromFormat($format, $request->query('end_date'))
                ->hour(0)
                ->minute(0)
                ->second(0);
            $query = $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $query = $query->orderBy('created_at', 'DESC');

        if($request->query('latest_only') === 'true') {
            return $query->first();
        }

        return $query->get();
    }
}
