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
                $newUserGroup = UserGroup::create([
                    'group_name' => $group['GroupName'],
                    'group_name_display' => '',
                    'group_type' => 'group',
                    'location_id' => \App\Location::all()->random()->id
                ]);
                $newUserGroup->update(['group_name_display' => $newUserGroup->location->name]);
            }
        }
    }
}
