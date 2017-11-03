<?php

use Illuminate\Database\Seeder;

class CountriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('countries')->insert([
            'name' => 'Canada',
            'abbreviation' => 'CA'
        ]);

        DB::table('countries')->insert([
            'name' => 'United States of America',
            'abbreviation' => 'US'
        ]);
    }
}
