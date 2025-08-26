<?php

namespace App\Http\Controllers\Factsheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FactsheetController extends Controller
{
    public function index()
    {
        return view('factsheet.index');
    }

    public function filter(Request $request)
    {
        // Get search parameters from request
        $search = $request->get('search', '');
        $searchType = $request->get('searchType', 'name');
        $substances = $request->get('substances', []);
        
        return view('factsheet.filter', compact('search', 'searchType', 'substances'));
    }

    public function search(Request $request)
    {
        
        
        return view('factsheet.index');
    }

}
