<?php

use Illuminate\Database\Seeder;

class TimezonesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('timezones')->insert([
            'name' => 'EST',
        ]);

        DB::table('timezones')->insert([
            'name' => 'MTC',
        ]);
    }
}
