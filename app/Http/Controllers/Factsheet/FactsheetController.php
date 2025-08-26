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
        
        // Convert to array if it's a single value
        if (!is_array($substances)) {
            $substances = $substances ? [$substances] : [];
        }
        
        return view('factsheet.filter', compact('request', 'search', 'searchType', 'substances'));
    }

    public function search(Request $request)
    {
        // Get selected substances from the request and ensure it's an array
        $substances = $request->get('substances', []);
        
        // Convert to array if it's a single value
        if (!is_array($substances)) {
            $substances = $substances ? [$substances] : [];
        }
        
        // If substances are selected, fetch their basic information
        $substanceData = [];
        if (!empty($substances)) {
            $substanceData = Substance::whereIn('id', $substances)->get();
        }
        
        return view('factsheet.index', compact('substanceData'));
    }

}
