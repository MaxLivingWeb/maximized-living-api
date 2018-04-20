<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\RegionalSubscriptionCount;

class HotfixRegionalSubscriptionCountInitData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fix Wisconsin
        $rsc = RegionalSubscriptionCount::find(27);
        if ($rsc) {
            $rsc->count = 4;
            $rsc->save();
        }

        // Fix Michigan
        $rsc = RegionalSubscriptionCount::find(45);
        if ($rsc) {
            $rsc->count = 4;
            $rsc->save();
        }

        $rsc = RegionalSubscriptionCount::find(33);
        if ($rsc) {
            $rsc->count = 4;
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
        // Reverse Wisconsin
        $rsc = RegionalSubscriptionCount::find(27);
        if ($rsc) {
            $rsc->count = 5;
            $rsc->save();
        }

        // Reverse Michigan
        $rsc = RegionalSubscriptionCount::find(45);
        if ($rsc) {
            $rsc->count = 5;
            $rsc->save();
        }

        $rsc = RegionalSubscriptionCount::find(33);
        if ($rsc) {
            $rsc->count = 5;
            $rsc->save();
        }
    }
}
