<?php

use Illuminate\Database\Seeder;

class AddressTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('address_types')->insert([
            'name' => 'Main Location'
        ]);

        DB::table('address_types')->insert([
            'name' => 'Shipping'
        ]);
    }
}
