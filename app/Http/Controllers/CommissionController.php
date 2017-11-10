<?php

namespace App\Http\Controllers;

use App\CommissionGroup;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function all()
    {
        return CommissionGroup::all();
    }

    public function getById($id)
    {
        return CommissionGroup::findOrFail($id);
    }

    public function add(Request $request)
    {
        return CommissionGroup::create([
            'percentage'    => floatval($request->input('percentage')),
            'description'   => $request->input('description')
        ]);
    }

    public function update($id, Request $request)
    {
        $group = CommissionGroup::findOrFail($id);

        $group->percentage = floatval($request->input('percentage'));
        $group->description = $request->input('description');

        $group->save();
    }

    public function delete($id)
    {
        $group = CommissionGroup::findOrFail($id);

        $group->delete();
    }
}
