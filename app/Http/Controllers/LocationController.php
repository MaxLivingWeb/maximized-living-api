<?php

namespace App\Http\Controllers;

use App\Location;

class LocationController extends Controller
{
    public function all()
    {
        return Location::all();
    }
}
