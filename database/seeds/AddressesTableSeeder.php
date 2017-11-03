<?php

use Illuminate\Database\Seeder;

class AddressesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('addresses')->insert([
            'address_1' => '2345 Yonge Street',
            'address_2' => 'Suite 905',
            'city_id' => 1
        ]);

        DB::table('addresses')->insert([
            'address_1' => '9737 Macleod Trail SW',
            'address_2' => 'Suite 370',
            'city_id' => 2
        ]);

        DB::table('addresses')->insert([
            'address_1' => '760 State Rt 10',
            'address_2' => 'Suite 205',
            'city_id' => 3
        ]);

        DB::table('addresses')->insert([
            'address_1' => '10743 Narcoossee Rd',
            'address_2' => 'Suite A-12',
            'city_id' => 4
        ]);
    }
}
