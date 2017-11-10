<?php

use Illuminate\Database\Seeder;
use App\Timezone;

class TimezonesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $timezones_array = [
            [
                'name' => 'EST',
            ],
            [
                'name' => 'MTC',
            ]
        ];

        foreach($timezones_array as $timezone) {
            Timezone::create([
                'name' => $timezone['name']
            ]);
        }
    }
}
