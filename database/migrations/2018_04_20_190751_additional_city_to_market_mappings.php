<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\City;

class AdditionalCityToMarketMappings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // City ID, Market ID
        $mappings = [
            [162, 4],
            [86, 5],
            [188, 10],
            [53, 10],
            [122, 34],
            [66, 58],
            [163, 63]
        ];

        foreach ($mappings as $mapping) {
            $cityId = $mapping[0];
            $marketId = $mapping[1];
            $city = City::find($cityId);
            if ($city) {
                $city->market_id = $marketId;
                $city->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // City ID, Market ID
        $mappings = [
            [162, 4],
            [86, 5],
            [188, 10],
            [53, 10],
            [122, 34],
            [66, 58],
            [163, 63]
        ];

        foreach ($mappings as $mapping) {
            $cityId = $mapping[0];
            $city = City::find($cityId);
            if ($city) {
                $city->market_id = NULL;
                $city->save();
            }
        }
    }
}
