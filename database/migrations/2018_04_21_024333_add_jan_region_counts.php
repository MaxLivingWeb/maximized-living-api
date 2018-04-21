<?php

use App\RegionalSubscriptionCount;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class AddJanRegionCounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Load historical data from Jan 31 2018
        // [[region_id, count]]
        $data = [
            [1, 2],
            [16, 1],
            [18, 3],
            [19,8],
            [22,14],
            [23,6],
            [28,4],
            [25,1],
            [26,7],
            [27,4],
            [30,2],
            [3,1],
            [35,4],
            [36,9],
            [38,4],
            [46,6],
            [40,1],
            [41,1],
            [48,4],
            [49,3],
            [9,5],
            [50,1],
            [51,2],
            [53,3],
            [55,4],
            [56,12],
            [62,4]
        ];
        
        foreach ($data as $row) {
            $regionId = $row[0];
            $count = $row[1];
            $creationDate = Carbon::createFromFormat('Y-m-d', '2018-1-31')
                ->hour(0)
                ->minute(0)
                ->second(0);

            $rsc = new RegionalSubscriptionCount();
            $rsc->region_id = $regionId;
            $rsc->count = $count;
            $rsc->created_at = $creationDate;
            $rsc->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
