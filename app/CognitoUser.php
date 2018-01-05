<?php

namespace App;

use Illuminate\Support\Facades\DB;

class CognitoUser
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function group()
    {
        $userGroupId = DB::table('usergroup_users')->where('user_id', $this->id)->first()->user_group_id ?? null;

        if(is_null($userGroupId)) {
            return null;
        }

        return UserGroup::with(['location','commission'])->find($userGroupId);
    }

    public function updateGroup($id)
    {
        DB::table('usergroup_users')->where('user_id', $this->id)->delete();

        UserGroup::where('group_name', $id)->first()->addUser($this->id);
    }
}
