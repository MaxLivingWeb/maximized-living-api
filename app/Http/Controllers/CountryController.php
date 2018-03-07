<?php

namespace App\Http\Controllers;

use App\Country;

class CountryController extends Controller
{
    /**
     * Get ALL Countries
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return Country::all();
    }

    /**
     * Retrieves a single Country
     *
     * @param integer $id The ID of the Country
     * @return array
     */
    public function getById($id)
    {
        return Country::findOrFail($id);
    }
}
