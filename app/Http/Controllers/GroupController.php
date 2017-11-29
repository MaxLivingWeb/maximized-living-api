<?php

namespace App\Http\Controllers;

use App\UserGroup;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function all()
    {
        return UserGroup::with(['commission', 'location'])->get();
    }

    public function getById($id)
    {
        return UserGroup::with(['commission', 'location'])->findOrFail($id);
    }

    public function getByName(Request $request)
    {
        return UserGroup::with(['commission', 'location'])->where('group_name', $request->input('name'))->firstOrFail();
    }

    public function add(Request $request)
    {
        $commission_id = null;

        if (!is_null($request->input('commission_id'))) {
            $commission_id = intval($request->input('commission_id'));
        }

        return UserGroup::create([
            'group_name' => $request->input('group_name'),
            'discount_id' => intval($request->input('discount_id')),
            'commission_id' => $commission_id
        ]);
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
