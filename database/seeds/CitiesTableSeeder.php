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
            City::create([
                'name' => $city['name'],
                'region_id' => $city['region_id']
            ]);
        }
    }
}