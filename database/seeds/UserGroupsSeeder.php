<?php

use Illuminate\Database\Seeder;
use App\UserGroup;

class UserGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cognito = new \App\Helpers\CognitoHelper();
        $groups = $cognito->getGroups();

        foreach($groups->get('Groups') as $group) {
            if (strpos($group['GroupName'], 'user.') === false) {
                UserGroup::create([
                    'group_name'    => $group['GroupName'],
                    'location_id'   => \App\Location::all()->random()->id
                ]);
            }
        }
    }
}
