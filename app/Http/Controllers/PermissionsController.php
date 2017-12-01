<?php

namespace App\Http\Controllers;

use App\UserPermission;

class PermissionsController extends Controller
{
    public function all()
    {
        return UserPermission::all();
    }
}
