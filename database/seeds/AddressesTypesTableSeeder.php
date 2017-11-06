<?php

use Illuminate\Database\Seeder;
use App\AddressType;

class AddressTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $address_types_array = [
            [
                'name' => 'Main Location'
            ],
            [
                'name' => 'Shipping'
            ]
        ];

        foreach($address_types_array as $address_type) {
            $new_address_type = new AddressType();

            $new_address_type->name = $address_type['name'];

            $new_address_type->save();
        }
    }
}
