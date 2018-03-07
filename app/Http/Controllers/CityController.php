<?php

namespace App\Http\Controllers;

use App\City;

class CityController extends Controller
{
    /**
     * Get ALL Cities
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return City::all();
    }

    /**
     * Retrieves a single City
     *
     * @param integer $id The ID of the location to retrieve users for.
     * @return array
     */
    public function getById($id)
    {
        return City::findOrFail($id);
    }
}
