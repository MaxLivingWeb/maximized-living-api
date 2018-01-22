<?php

namespace App\Http\Controllers;

use App\Location;

class LocationController extends Controller
{
    /**
     * Retrieves a list of all Cognito users associated with a given location.
     *
     * @param integer $id The ID of the location to retrieve users for.
     * @return array
     */
    public function getUsersById($id)
    {
        $location = Location::with('userGroup')->findOrFail($id);
        return $location->listUsers();
    }
}
