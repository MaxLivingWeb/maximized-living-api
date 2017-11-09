<?php

use Illuminate\Database\Seeder;
use App\Country;

class CountriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $countries_array = [
            [
                'name' => 'Canada',
                'abbreviation' => 'CA'
            ],
            [
                'name' => 'United States of America',
                'abbreviation' => 'US'
            ]
        ];

        foreach($countries_array as $country) {
            Country::create([
                'name' => $country['name'],
                'abbreviation' => $country['abbreviation']
            ]);
        }
    }
}
