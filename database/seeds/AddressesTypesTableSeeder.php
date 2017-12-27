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
            ],
            [
                'name' => 'Billing'
            ]
        ];

        foreach($address_types_array as $address_type) {
            AddressType::create([
                'name' => $address_type['name']
            ]);
        }
    }
}
