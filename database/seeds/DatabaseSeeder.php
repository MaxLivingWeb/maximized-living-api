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
        $this->call([
            CountriesTableSeeder::class,
            RegionsTableSeeder::class,
            CitiesTableSeeder::class,
            AddressesTableSeeder::class,
            TimezonesTableSeeder::class,
            LocationsTableSeeder::class,
            AddressTypesTableSeeder::class,
            LocationsAddressesTableSeeder::class,
            CommissionGroupsSeeder::class,
            UserPermissionsSeeder::class
        ]);
    }
}
