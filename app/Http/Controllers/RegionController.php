<?php

namespace App\Http\Controllers;

use App\Region;

class RegionController extends Controller
{
    /**
     * Get ALL Regions
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function all()
    {
        return Region::all();
    }

    /**
     * Retrieves a single Region
     *
     * @param integer $id The ID of the Region
     * @return array
     */
    public function getById($id)
    {
        return Region::findOrFail($id);
    }
}
