<?php

use Illuminate\Database\Seeder;
use App\Region;

class RegionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $regions_array = [
            [
                'name' => 'Alberta',
                'abbreviation' => 'AB',
                'country_id' => 1
            ],
            [
                'name' => 'British Columbia',
                'abbreviation' => 'BC',
                'country_id' => 1
            ],
            [
                'name' => 'Manitoba',
                'abbreviation' => 'MB',
                'country_id' => 1
            ],
            [
                'name' => 'New Brunswick',
                'abbreviation' => 'NB',
                'country_id' => 1
            ],
            [
                'name' => 'Newfoundland and Labrador',
                'abbreviation' => 'NL',
                'country_id' => 1
            ],
            [
                'name' => 'Nova Scotia',
                'abbreviation' => 'NS',
                'country_id' => 1
            ],
            [
                'name' => 'Northwest Territories',
                'abbreviation' => 'NT',
                'country_id' => 1
            ],
            [
                'name' => 'Nunavut',
                'abbreviation' => 'NU',
                'country_id' => 1
            ],
            [
                'name' => 'Ontario',
                'abbreviation' => 'ON',
                'country_id' => 1
            ],
            [
                'name' => 'Prince Edward Island',
                'abbreviation' => 'PE',
                'country_id' => 1
            ],
            [
                'name' => 'Quebec',
                'abbreviation' => 'QC',
                'country_id' => 1
            ],
            [
                'name' => 'Saskatchewan',
                'abbreviation' => 'SK',
                'country_id' => 1
            ],
            [
                'name' => 'Yukon',
                'abbreviation' => 'YT',
                'country_id' => 1
            ],
            [
                'name' => 'Alabama',
                'abbreviation' => 'AL',
                'country_id' => 2
            ],
            [
                'name' => 'Alaska',
                'abbreviation' => 'AK',
                'country_id' => 2
            ],
            [
                'name' => 'Arizona',
                'abbreviation' => 'AR',
                'country_id' => 2
            ],
            [
                'name' => 'Arkansas',
                'abbreviation' => 'AR',
                'country_id' => 2
            ],
            [
                'name' => 'California',
                'abbreviation' => 'CA',
                'country_id' => 2
            ],
            [
                'name' => 'Colorado',
                'abbreviation' => 'CO',
                'country_id' => 2
            ],
            [
                'name' => 'Connecticut',
                'abbreviation' => 'CT',
                'country_id' => 2
            ],
            [
                'name' => 'Delaware',
                'abbreviation' => 'DE',
                'country_id' => 2
            ],
            [
                'name' => 'Florida',
                'abbreviation' => 'FL',
                'country_id' => 2
            ],
            [
                'name' => 'Georgia',
                'abbreviation' => 'GA',
                'country_id' => 2
            ],
            [
                'name' => 'Hawaii',
                'abbreviation' => 'HI',
                'country_id' => 2
            ],
            [
                'name' => 'Idaho',
                'abbreviation' => 'ID',
                'country_id' => 2
            ],
            [
                'name' => 'Illinois',
                'abbreviation' => 'IL',
                'country_id' => 2
            ],
            [
                'name' => 'Indiana',
                'abbreviation' => 'IN',
                'country_id' => 2
            ],
            [
                'name' => 'Iowa',
                'abbreviation' => 'IA',
                'country_id' => 2
            ],
            [
                'name' => 'Kansas',
                'abbreviation' => 'KS',
                'country_id' => 2
            ],
            [
                'name' => 'Kentucky',
                'abbreviation' => 'KY',
                'country_id' => 2
            ],
            [
                'name' => 'Louisiana',
                'abbreviation' => 'LA',
                'country_id' => 2
            ],
            [
                'name' => 'Maine',
                'abbreviation' => 'ME',
                'country_id' => 2
            ],
            [
                'name' => 'Maryland',
                'abbreviation' => 'MD',
                'country_id' => 2
            ],
            [
                'name' => 'Massachusetts',
                'abbreviation' => 'MA',
                'country_id' => 2
            ],
            [
                'name' => 'Michigan',
                'abbreviation' => 'MI',
                'country_id' => 2
            ],
            [
                'name' => 'Minnesota',
                'abbreviation' => 'MN',
                'country_id' => 2
            ],
            [
                'name' => 'Mississippi',
                'abbreviation' => 'MS',
                'country_id' => 2
            ],
            [
                'name' => 'Missouri',
                'abbreviation' => 'MO',
                'country_id' => 2
            ],
            [
                'name' => 'Montana',
                'abbreviation' => 'MT',
                'country_id' => 2
            ],
            [
                'name' => 'Nebraska',
                'abbreviation' => 'NE',
                'country_id' => 2
            ],
            [
                'name' => 'Nevada',
                'abbreviation' => 'NV',
                'country_id' => 2
            ],
            [
                'name' => 'New Hampshire',
                'abbreviation' => 'NH',
                'country_id' => 2
            ],
            [
                'name' => 'New Jersey',
                'abbreviation' => 'NJ',
                'country_id' => 2
            ],
            [
                'name' => 'New Mexico',
                'abbreviation' => 'NM',
                'country_id' => 2
            ],
            [
                'name' => 'New York',
                'abbreviation' => 'NY',
                'country_id' => 2
            ],
            [
                'name' => 'North Carolina',
                'abbreviation' => 'NC',
                'country_id' => 2
            ],
            [
                'name' => 'North Dakota',
                'abbreviation' => 'ND',
                'country_id' => 2
            ],
            [
                'name' => 'Ohio',
                'abbreviation' => 'OH',
                'country_id' => 2
            ],
            [
                'name' => 'Oklahoma',
                'abbreviation' => 'OK',
                'country_id' => 2
            ],
            [
                'name' => 'Oregon',
                'abbreviation' => 'OR',
                'country_id' => 2
            ],
            [
                'name' => 'Pennsylvania',
                'abbreviation' => 'PA',
                'country_id' => 2
            ],
            [
                'name' => 'Rhode Island',
                'abbreviation' => 'RI',
                'country_id' => 2
            ],
            [
                'name' => 'South Carolina',
                'abbreviation' => 'SC',
                'country_id' => 2
            ],
            [
                'name' => 'South Dakota',
                'abbreviation' => 'SD',
                'country_id' => 2
            ],
            [
                'name' => 'Tennessee',
                'abbreviation' => 'TN',
                'country_id' => 2
            ],
            [
                'name' => 'Texas',
                'abbreviation' => 'TX',
                'country_id' => 2
            ],
            [
                'name' => 'Utah',
                'abbreviation' => 'UT',
                'country_id' => 2
            ],
            [
                'name' => 'Vermont',
                'abbreviation' => 'VT',
                'country_id' => 2
            ],
            [
                'name' => 'Virginia',
                'abbreviation' => 'VA',
                'country_id' => 2
            ],
            [
                'name' => 'Washington',
                'abbreviation' => 'WA',
                'country_id' => 2
            ],
            [
                'name' => 'West Virginia',
                'abbreviation' => 'WV',
                'country_id' => 2
            ],
            [
                'name' => 'Wisconsin',
                'abbreviation' => 'WI',
                'country_id' => 2
            ],
            [
                'name' => 'Wyoming',
                'abbreviation' => 'WY',
                'country_id' => 2
            ],
            [
                'name' => 'American Samoa',
                'abbreviation' => 'AS',
                'country_id' => 2
            ],
            [
                'name' => 'District of Columbia',
                'abbreviation' => 'DC',
                'country_id' => 2
            ],
            [
                'name' => 'Federated States of Micronesia',
                'abbreviation' => 'FM',
                'country_id' => 2
            ],
            [
                'name' => 'Guam',
                'abbreviation' => 'GU',
                'country_id' => 2
            ],
            [
                'name' => 'Marshall Islands',
                'abbreviation' => 'MH',
                'country_id' => 2
            ],
            [
                'name' => 'Northern Mariana Islands',
                'abbreviation' => 'MP',
                'country_id' => 2
            ],
            [
                'name' => 'Palau',
                'abbreviation' => 'PW',
                'country_id' => 2
            ],
            [
                'name' => 'Puerto Rico',
                'abbreviation' => 'PR',
                'country_id' => 2
            ],
            [
                'name' => 'Virgin Islands',
                'abbreviation' => 'VI',
                'country_id' => 2
            ]
        ];

        foreach($regions_array as $region) {
            Region::create([
                'name' => $region['name'],
                'abbreviation' => $region['abbreviation'],
                'country_id' => $region['country_id']
            ]);
        }
    }
}
