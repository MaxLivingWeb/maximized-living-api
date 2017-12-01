<?php

use Illuminate\Database\Seeder;
use App\UserPermission;

class UserPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission_array = [
            [
                'key' => 'dashboard-usermanagement',
                'name' => 'Admin Dashboard - User Management'
            ],
            [
                'key' => 'dashboard-commissions',
                'name' => 'Admin Dashboard - Commissions'
            ],
            [
                'key' => 'dashboard-wholesaler',
                'name' => 'Admin Dashboard - Wholesaler'
            ],
            [
                'key' => 'public-website',
                'name' => 'Public Website'
            ],
            [
                'key' => 'contentportal',
                'name' => 'Content Portal'
            ]
        ];

        foreach($permission_array as $permission) {
            UserPermission::create([
                'key' =>  $permission['key'],
                'name' => $permission['name']
            ]);
        }
    }
}
