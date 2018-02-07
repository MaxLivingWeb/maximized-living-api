<?php

namespace App\Http\Controllers;

use App\Location;
use App\UserGroup;

class LocationController extends Controller
{
    /**
     * Get ALL Locations
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
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

    /**
     * Get UserGroup for this Location
     * @param $id
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|void
     */
    public function getUserGroupById($id)
    {
        $location = Location::with('userGroup')->findOrFail($id);

        if (empty($location->userGroup)) {
            return;
        }

        return UserGroup::with(['location', 'commission'])->findOrFail($location->userGroup->id);
    }
}
