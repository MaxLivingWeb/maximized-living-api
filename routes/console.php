<?php

use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('wholesale', function () {
    try {
        $regions = [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'DC' => 'District of Columbia',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            'PR' => 'Puerto Rico',
            'AB' => 'Alberta',
            'BC' => 'British Columbia',
            'MB' => 'Manitoba',
            'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador',
            'NT' => 'Northwest Territories',
            'NS' => 'Nova Scotia',
            'NU' => 'Nunavut',
            'ON' => 'Ontario',
            'PE' => 'Prince Edward Island',
            'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
            'YT' => 'Yukon Territory'
        ];
        $countries = [
            'CA' => 'Canada',
            'US' => 'United States of America',
            'GB' => 'United Kingdom',
            'PH' => 'Philippines',
            'AU' => 'Australia',
            'ZA' => 'South Africa'
        ];

        $csv = array_map('str_getcsv', file('wholesale.csv'));

        //remove header
        unset($csv[0]);

        $cognito = new \App\Helpers\CognitoHelper();
        $allUsers = collect($cognito->listUsers());

        $shopify = new \App\Helpers\ShopifyHelper();
        $priceRules = collect($shopify->getPriceRules());

        foreach ($csv as $user) {
            $legacy_affiliate_id = intval($user[1]);
            $email = $user[8];

            $params = [
                'group_name' => $user[12] !== '' ? preg_replace('/\s+/', '', $user[12]) : 'user.' . $email,
                'group_name_display' => $user[12] !== '' ? $user[12] : $user[10] . ' ' . $user[4],
                'legacy_affiliate_id' => $legacy_affiliate_id
            ];

            $cognitoUser = $allUsers->where('email', $email)->first();

            if (is_null($cognitoUser)) {
                $this->info('error, no cognito user found for ' . $email);
            }

            $group = \App\UserGroup::where('legacy_affiliate_id', $legacy_affiliate_id)->first();
            if (!is_null($group)) {
                $this->info('duplicate group');
            }

            //match discount group
            $priceRule = $priceRules->where('title', $user[3])->first();
            if (!is_null($priceRule)) {
                $params['discount_id'] = $priceRule->id;
            }

            //tag user with discount group
            $shopify->addCustomerTag($cognitoUser['shopifyId'], $priceRule->title);

            $newWholesaleGroup = \App\UserGroup::create($params);
            $newWholesaleGroup->addUser($cognitoUser['id']);

            $cityId = \App\City::checkCity($countries[$user[9]], $regions[$user[5]] ?? 'Other', $user[11]);

            $address = \App\Address::create([
                'address_1' => $user[6],
                'address_2' => '',
                'zip_postal_code' => $user[15],
                'city_id'   => $cityId,
                'latitude' => 0,
                'longitude' => 0
            ]);

            $address->groups()->attach(
                $newWholesaleGroup->id,
                ['address_type_id' => \App\AddressType::firstOrCreate(['name' => 'Wholesale Billing'])->id]
            );

            if($user[17] !== '' && $user[18] !== '' && $user[19] !== '' && $user[22] !== '' && $user[16] !== '') {
                $cityId2 = \App\City::checkCity($countries[$user[17]], $regions[$user[18]] ?? 'Other', $user[19]);
                $shippingAddress = \App\Address::create([
                    'address_1' => $user[22],
                    'address_2' => '',
                    'zip_postal_code' => $user[16],
                    'city_id' => $cityId2,
                    'latitude' => 0,
                    'longitude' => 0
                ]);

                $shippingAddress->groups()->attach(
                    $newWholesaleGroup->id,
                    ['address_type_id' => \App\AddressType::firstOrCreate(['name' => 'Wholesale Shipping'])->id]
                );
            }

            $this->info('complete: ' . $email);
        }
    }
    catch (\Exception $e) {
        $this->info($e->getMessage());
    }

    $this->info('done');
});
