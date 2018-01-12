<?php

use Illuminate\Database\Seeder;
use App\Address;

class AddressesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $addresses_array = [
            [
                'address_1' => '2345 Yonge Street',
                'address_2' => 'Suite 905',
                'city_id' => 1,
                'zip_postal_code' => 'M4P2E5',
                'latitude' => '43.7087621',
                'longitude' => '-79.3987508',
            ],
            [
                'address_1' => '9737 Macleod Trail SW',
                'address_2' => 'Suite 370',
                'city_id' => 2,
                'zip_postal_code' => 'M4P2E5',
                'latitude' => '43.7087621',
                'longitude' => '-79.3987508',
            ],
            [
                'address_1' => '760 State Rt 10',
                'address_2' => 'Suite 205',
                'city_id' => 3,
                'zip_postal_code' => 'M4P2E5',
                'latitude' => '43.7087621',
                'longitude' => '-79.3987508',
            ],
            [
                'address_1' => '10743 Narcoossee Rd',
                'address_2' => 'Suite A-12',
                'city_id' => 4,
                'zip_postal_code' => 'M4P2E5',
                'latitude' => '43.7087621',
                'longitude' => '-79.3987508',
            ]
        ];

        foreach($addresses_array as $address) {
            Address::create([
                'address_1' =>  $address['address_1'],
                'address_2' => $address['address_2'],
                'city_id' => $address['city_id'],
                'zip_postal_code' => $address['zip_postal_code'],
                'latitude' => $address['latitude'],
                'longitude' => $address['longitude'],
            ]);
        }
    }
}
