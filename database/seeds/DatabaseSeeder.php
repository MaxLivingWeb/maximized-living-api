<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //$this->call(UsersTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(RegionsTableSeeder::class);
        $this->call(CitiesTableSeeder::class);
        $this->call(AddressesTableSeeder::class);
        $this->call(TimezonesTableSeeder::class);
        $this->call(LocationsTableSeeder::class);
        $this->call(AddressTypesTableSeeder::class);
        $this->call(LocationsAddressesTableSeeder::class);
    }
}
