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
        $locations = Location::all();

        return collect($locations)
            ->each(function($location){
                $location->user_group = $location->userGroup;
            });
    }

    /**
     * Retrieves Locaiton by provided Location ID
     * @param Request $request
     * @param int $id Location ID
     * @return array
     */
    public function getById($id)
    {
        $location = Location::findOrFail($id);
        return response()->json($location);
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
