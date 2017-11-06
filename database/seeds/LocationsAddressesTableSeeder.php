<?php

use Illuminate\Database\Seeder;

class LocationsAddressesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $locations_addresses_array = [
            [
                'location_id' => 1,
                'address_id' => 1,
                'address_type_id' => 1
            ],
            [
                'location_id' => 2,
                'address_id' => 2,
                'address_type_id' => 1
            ],
            [
                'location_id' => 3,
                'address_id' => 3,
                'address_type_id' => 1
            ],
            [
                'location_id' => 4,
                'address_id' => 4,
                'address_type_id' => 1
            ]
        ];

        foreach($locations_addresses_array as $la) {
            DB::table('locations_addresses')->insert([
                'location_id' => $la['location_id'],
                'address_id' => $la['address_id'],
                'address_type_id' => $la['address_type_id']
            ]);
        }
    }
}
