<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class DateRequestHelper
{
    /**
     * Get Start and End date from the current Request
     */
    public static function getDateObject(Request $request)
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

        return (object)[
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }
}
