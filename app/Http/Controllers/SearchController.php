<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\SearchHelper;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        $results = SearchHelper::productSearch($query ?? '');
        dd($results);
        
        return $results;
    }
}
