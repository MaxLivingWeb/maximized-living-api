<?php

namespace App\Http\Controllers;

use App\Location;
use App\UserGroup;

class LocationController extends Controller
{
    public function all()
    {
        return Location::all();
    }

    /**
     * Retrieves a list of all Cognito users associated with a given location.
     *
     * @param integer $id The ID of the location to retrieve users for.
     * @return array
     */
    public function getUsersById($id)
    {
        $location = Location::with('userGroup')->findOrFail($id);
        return response()->json($location->listUsers());
    }

    public function getUserGroupById($id)
    {
        $location = Location::with('userGroup')->findOrFail($id);

        $userGroup = UserGroup::with('commission')->findOrFail($location->userGroup->id);

        return $userGroup;
    }
}
