<?php

namespace App\Http\Controllers;

use App\Helpers\CognitoHelper;
use App\UserGroup;
use Aws\Exception\AwsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        try {
            $fields = [
                'group_name' => 'required'
            ];
            if($request->has('commission')) {
                $fields = array_merge($fields, [
                    'commission.id'                     => 'required',
                    'commission.account_number'         => 'required',
                    'commission.branch_number'          => 'required',
                    'commission.institution_number'     => 'required'
                ]);
            }
            $request->validate($fields);

            //TODO: What do we do with banking info?

            $commission_id = null;
            if (!is_null($request->input('commission.id'))) {
                $commission_id = intval($request->input('commission.id'));
            }

            $location_id = null;
            if (!is_null($request->input('location_id'))) {
                $location_id = intval($request->input('location_id'));
            }

            $cognito = new CognitoHelper();
            $cognito->createGroup($request->input('group_name'), '');

            return UserGroup::create([
                'group_name'    => $request->input('group_name'),
                'discount_id'   => intval($request->input('discount_id')),
                'commission_id' => $commission_id,
                'location_id'   => $location_id
            ]);
        }
        catch (ValidationException $e) {
            return response()->json($e->errors(), 400);
        }
        catch (AwsException $e) {
            dd($e);
            return response()->json($e->getAwsErrorMessage(), 400);
        }
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
