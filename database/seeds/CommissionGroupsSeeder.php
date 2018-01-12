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
                'percentage' => 20.0,
                'description' => 'Client - Student - 20%'
            ],
            [
                'percentage' => 30.0,
                'description' => 'Client - Student - 30%'
            ],
            [
                'percentage' => 35.0,
                'description' => 'Client - Student - 35%'
            ],
            [
                'percentage' => 40.0,
                'description' => 'Client - Student - 40%'
            ],
            [
                'percentage' => 20.0,
                'description' => 'Client - MLHC - 20%'
            ],
            [
                'percentage' => 30.0,
                'description' => 'Client - MLHC - 30%'
            ],
            [
                'percentage' => 35.0,
                'description' => 'Client - MLHC - 35%'
            ],
            [
                'percentage' => 40.0,
                'description' => 'Client - MLHC - 40%'
            ],
            [
                'percentage' => 20.0,
                'description' => 'Client - Partner - 20%'
            ],
            [
                'percentage' => 30.0,
                'description' => 'Client - Partner - 30%'
            ],
            [
                'percentage' => 35.0,
                'description' => 'Client - Partner - 35%'
            ],
            [
                'percentage' => 40.0,
                'description' => 'Client - Partner - 40%'
            ],
            [
                'percentage' => 20.0,
                'description' => 'Marketing Affiliate - 20%'
            ],
            [
                'percentage' => 30.0,
                'description' => 'Marketing Affiliate - 30%'
            ],
            [
                'percentage' => 35.0,
                'description' => 'Marketing Affiliate - 35%'
            ],
            [
                'percentage' => 40.0,
                'description' => 'Marketing Affiliate - 40%'
            ],
            [
                'percentage' => 0.0,
                'description' => 'Client - Partner - 0%'
            ],
            [
                'percentage' => 0.0,
                'description' => 'Client - MLHC - 0%'
            ],
            [
                'percentage' => 0.0,
                'description' => 'Client - Student - 0%'
            ],
            [
                'percentage' => 0.0,
                'description' => 'Wholesaler - Partner - 0%'
            ],
            [
                'percentage' => 0.0,
                'description' => 'Marketing Affiliate - 0%'
            ]
        ];

        foreach($commissions_groups as $commission) {
            CommissionGroup::create([
                'percentage' =>  $commission['percentage'],
                'description' => $commission['description']
            ]);
        }
    }
}
