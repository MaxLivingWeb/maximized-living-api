<?php

use Illuminate\Database\Seeder;
use App\Location;

class LocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $locations_array = [
            [
                'name' => 'Castlefield Chiropractic',
                'zip_postal_code' => 'M4P2E5',
                'latitude' => '43.7087621',
                'longitude' => '-79.3987508',
                'telephone' => '555-123-9876',
                'telephone_ext' => '765',
                'fax' => '123-651-7324',
                'email' => 'castlefield@maxliving.com',
                'vanity_website_url' => 'castlefield-chiropractic',
                'slug' => 'castlefield-chiropractic',
                'pre_open_display_date' => '12',
                'opening_date' => '05-03-2011',
                'closing_date' => '04-02-2020',
                'daylight_savings_applies' => true,
                'operating_hours' => '',
                'timezone_id' => 1
            ],
            [
                'name' => 'Elevation Chiropractic',
                'zip_postal_code' => 'T2J0P6',
                'latitude' => '50.966858',
                'longitude' => '-114.073013',
                'telephone' => '555-325-9996',
                'telephone_ext' => '111',
                'fax' => '123-892-1520',
                'email' => 'elevation@maxliving.com',
                'vanity_website_url' => 'elevation-chiropractic',
                'slug' => 'elevation-chiropractic',
                'pre_open_display_date' => '14',
                'opening_date' => '04-03-2007',
                'closing_date' => '07-02-2021',
                'daylight_savings_applies' => true,
                'operating_hours' => '',
                'timezone_id' => 2
            ],
            [
                'name' => 'Ferguson Life Chiropractic Centers',
                'zip_postal_code' => '07981',
                'latitude' => '40.8260888',
                'longitude' => '-74.4223778',
                'telephone' => '555-985-9234',
                'telephone_ext' => '171',
                'fax' => '444-892-1520',
                'email' => 'ferguson@maxliving.com',
                'vanity_website_url' => 'ferguson-life',
                'slug' => 'ferguson-life',
                'pre_open_display_date' => '9',
                'opening_date' => '04-03-2014',
                'closing_date' => '07-02-2021',
                'daylight_savings_applies' => true,
                'operating_hours' => '',
                'timezone_id' => 1
            ],
            [
                'name' => 'Lake Nona Family Chiropractic',
                'zip_postal_code' => '32832',
                'latitude' => '28.4111915',
                'longitude' => '-81.2428853',
                'telephone' => '987-975-9214',
                'telephone_ext' => '009',
                'fax' => '423-852-9920',
                'email' => 'lakenona@maxliving.com',
                'vanity_website_url' => 'lake-nona',
                'slug' => 'lake-nona',
                'pre_open_display_date' => '7',
                'opening_date' => '08-03-2012',
                'closing_date' => '02-02-2022',
                'daylight_savings_applies' => true,
                'operating_hours' => '',
                'timezone_id' => 1
            ]
        ];

        foreach ($locations_array as $location) {
            $new_location = new Location();

            $new_location->name = $location['name'];
            $new_location->zip_postal_code = $location['zip_postal_code'];
            $new_location->latitude = $location['latitude'];
            $new_location->longitude = $location['longitude'];
            $new_location->telephone = $location['telephone'];
            $new_location->telephone_ext = $location['telephone_ext'];
            $new_location->fax = $location['fax'];
            $new_location->email = $location['email'];
            $new_location->vanity_website_url = $location['vanity_website_url'];
            $new_location->slug = $location['slug'];
            $new_location->pre_open_display_date = $location['pre_open_display_date'];
            $new_location->opening_date = $location['opening_date'];
            $new_location->closing_date = $location['closing_date'];
            $new_location->daylight_savings_applies = $location['daylight_savings_applies'];
            $new_location->operating_hours = $location['operating_hours'];
            $new_location->timezone_id = $location['timezone_id'];

            $new_location->save();
        }
    }
}
