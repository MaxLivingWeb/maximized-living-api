<?php

use Illuminate\Database\Seeder;
use App\City;

class CitiesTableSeeder extends Seeder
{

    public function run()
    {
        $cities_array = [
            [
                'name' => 'Toronto',
                'region_id' => 1],
            [
                'name' => 'Calgary',
                'region_id' => 2
            ],
            [
                'name' => 'Whippany',
                'region_id' => 3
            ],
            [
                'name' => 'Orlando',
                'region_id' => 4
            ]
        ];

        foreach($cities_array as $city) {
            $new_city = new City();

            $new_city->name = $city['name'];
            $new_city->region_id = $city['region_id'];

            $new_city->save();
        }
    }
}