<?php

namespace App\Http\Controllers;

use App\CommissionGroup;
use Illuminate\Http\Request;

// TODO - Remove this file? It is not being used.

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
            'description'   => $request->input('description'),
            'store_tax_number' => $request->input('store_tax_number'),
        ]);
    }

    public function update($id, Request $request)
    {
        $group = CommissionGroup::findOrFail($id);

        $group->percentage = floatval($request->input('percentage'));
        $group->description = $request->input('description');
        $group->store_tax_number = $request->input('store_tax_number');

        $group->save();
    }

    public function delete($id)
    {
        $group = CommissionGroup::findOrFail($id);

        $group->delete();
    }
}
