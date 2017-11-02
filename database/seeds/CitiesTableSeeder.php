<?php

use Illuminate\Database\Seeder;

class CitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('cities')->insert([
            'name' => 'Toronto',
            'region_id' => 1
        ]);

        DB::table('cities')->insert([
            'name' => 'Calgary',
            'region_id' => 2
        ]);

        DB::table('cities')->insert([
            'name' => 'Whippany',
            'region_id' => 3
        ]);

        DB::table('cities')->insert([
            'name' => 'Orlando',
            'region_id' => 4
        ]);
    }
}