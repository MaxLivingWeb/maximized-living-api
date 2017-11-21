<?php

namespace App\Http\Controllers;

use App\UserGroup;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function all()
    {
        return UserGroup::all();
    }

    public function getById($id)
    {
        return UserGroup::findOrFail($id);
    }

    public function getByName(Request $request)
    {
        return UserGroup::where('group_name', $request->input('name'))->firstOrFail();
    }

    public function add(Request $request)
    {
        UserGroup::create(['group_name' => $request->input('group_name'), 'discount_id' => intval($request->input('discount_id'))]);
    }

    public function update($id, Request $request)
    {
        $group = UserGroup::findOrFail($id);

        $group->discount_id = intval($request->input('discount_id'));

        $group->save();
    }

    public function delete($id)
    {
        $group = UserGroup::findOrFail($id);

        $group->delete();
    }
}
