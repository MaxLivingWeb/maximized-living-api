<?php

use App\MarketSubscriptionCount;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class TrackMarketSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_subscription_counts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('market_id')->unsigned();
            $table->foreign('market_id')->references('id')->on('markets');
            $table->integer('count')->unsigned();
            $table->softDeletes();
            $table->timestamps();
        });

        // Load historical data from Apr 10 2018
        // [[market_id, count]]
        $data = [
            [1,2],
            [2,1],
            [3,1],
            [4,1],
            [5,6],
            [6,1],
            [7,2],
            [8,5],
            [9,1],
            [10,1],
            [11,1],
            [12,1],
            [13,1],
            [14,1],
            [15,1],
            [16,3],
            [17,1],
            [18,3],
            [19,1],
            [20,1],
            [21,1],
            [22,1],
            [23,4],
            [24,1],
            [25,4],
            [26,1],
            [27,1],
            [28,1],
            [29,1],
            [30,1],
            [31,2],
            [32,6],
            [33,1],
            [34,1],
            [35,3],
            [36,1],
            [37,1],
            [38,2],
            [39,1],
            [40,2],
            [41,1],
            [42,1],
            [43,1],
            [44,2],
            [45,1],
            [46,2],
            [47,1],
            [48,2],
            [49,2],
            [50,1],
            [51,1],
            [52,1],
            [53,1],
            [54,2],
            [55,1],
            [56,3],
            [57,1],
            [58,7],
            [59,2],
            [60,2],
            [61,1],
            [62,1],
            [63,1]
        ];

        foreach ($data as $row) {
            $marketId = $row[0];
            $count = $row[1];
            $creationDate = Carbon::createFromFormat('Y-m-d', '2018-4-10')
                ->hour(0)
                ->minute(0)
                ->second(0);

            $rsc = new MarketSubscriptionCount();
            $rsc->market_id = $marketId;
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
        Schema::dropIfExists('market_subscription_counts');
    }
}
