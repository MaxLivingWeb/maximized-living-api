<?php

use Illuminate\Database\Seeder;
use App\CommissionGroup;

class CommissionGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $commissions_groups = [
            [
                'percentage' => 5.0,
                'description' => 'this is a description of a 5% commission'
            ],
            [
                'percentage' => 2.5,
                'description' => 'this is a description of a 2.5% commission'
            ],
            [
                'percentage' => 10.01,
                'description' => 'this is a description of a 10.01% commission'
            ],
            [
                'percentage' => 99.9,
                'description' => 'this is a description of a 99.9% commission'
            ],
            [
                'percentage' => 0.2,
                'description' => 'this is a description of a 0.2% commission'
            ],
        ];

        foreach($commissions_groups as $commission) {
            CommissionGroup::create([
                'percentage' =>  $commission['percentage'],
                'description' => $commission['description']
            ]);
        }
    }
}
