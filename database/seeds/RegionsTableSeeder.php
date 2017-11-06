<?php

use Illuminate\Database\Seeder;
use App\Region;

class RegionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $regions_array = [
            [
                'name' => 'Ontario',
                'abbreviation' => 'ON',
                'country_id' => 1
            ],
            [
                'name' => 'Alberta',
                'abbreviation' => 'AB',
                'country_id' => 1
            ],
            [
                'name' => 'New Jersey',
                'abbreviation' => 'NJ',
                'country_id' => 2
            ],
            [
                'name' => 'Florida',
                'abbreviation' => 'FL',
                'country_id' => 2
            ]
        ];

        foreach($regions_array as $region) {
            $new_region = new Region();

            $new_region->name = $region['name'];
            $new_region->abbreviation = $region['abbreviation'];
            $new_region->country_id = $region['country_id'];

            $new_region->save();
        }
    }
}
