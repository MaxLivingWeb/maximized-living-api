<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Market;
use App\City;

class MapDeletedClinicsCitiesToMarkets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Make "Eugene" market
        $market = new Market();
        $market->name = "Eugene";
        $market->save();

        // Map "Eugene" to "Eugene" market
        $eugeneCity = City::find(5);
        if ($eugeneCity) {
            $eugeneCity->market_id = $market->id;
            $eugeneCity->save();
        }

        // Map "Plano" to "Dallas-Fort Worth" market
        $planoCity = City::find(39);
        if ($planoCity) {
            $planoCity->market_id = 58; // Dallas-Fort Worth
            $planoCity->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $planoCity = City::find(39);
        if ($planoCity) {
            $planoCity->market_id = null;
            $planoCity->save();
        }

        $eugeneCity = City::find(5);
        if ($eugeneCity) {
            $eugeneCity->market_id = null;
            $eugeneCity->save();
        }

        $eugeneMarket = Market::where('name', 'Eugene');
        if ($eugeneMarket) {
            $eugeneMarket->delete();
        }
    }
}
