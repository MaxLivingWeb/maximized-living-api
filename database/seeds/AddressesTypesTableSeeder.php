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
        DB::table('addressTypes')->insert([
            'name' => 'Main Location'
        ]);

        DB::table('addressTypes')->insert([
            'name' => 'Shipping'
        ]);
    }
}
