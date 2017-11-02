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
        DB::table('locationsAddresses')->insert([
            'location_id' => 1,
            'address_id' => 1,
            'address_type_id' => 1
        ]);

        DB::table('locationsAddresses')->insert([
            'location_id' => 2,
            'address_id' => 2,
            'address_type_id' => 1
        ]);

        DB::table('locationsAddresses')->insert([
            'location_id' => 3,
            'address_id' => 3,
            'address_type_id' => 1
        ]);

        DB::table('locationsAddresses')->insert([
            'location_id' => 4,
            'address_id' => 4,
            'address_type_id' => 1
        ]);
    }
}
