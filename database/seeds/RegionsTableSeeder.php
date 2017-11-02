<?php

use Illuminate\Database\Seeder;

class RegionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('regions')->insert([
            'name' => 'Ontario',
            'abbreviation' => 'ON',
            'country_id' => 1
        ]);

        DB::table('regions')->insert([
            'name' => 'Alberta',
            'abbreviation' => 'AB',
            'country_id' => 1
        ]);

        DB::table('regions')->insert([
            'name' => 'New Jersey',
            'abbreviation' => 'NJ',
            'country_id' => 2
        ]);

        DB::table('regions')->insert([
            'name' => 'Florida',
            'abbreviation' => 'FL',
            'country_id' => 2
        ]);
    }
}
