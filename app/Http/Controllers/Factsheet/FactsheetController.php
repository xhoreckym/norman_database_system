<?php

namespace App\Http\Controllers\Factsheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Susdat\Substance;
use App\Models\Factsheet\FactsheetEntity;
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
        
        // Convert to array if it's a single value, but ensure only one substance
        if (!is_array($substances)) {
            $substances = $substances ? [$substances] : [];
        }
        
        // If multiple substances are selected, take only the first one
        if (count($substances) > 1) {
            $substances = [reset($substances)];
        }
        
        return view('factsheet.filter', compact('request', 'search', 'searchType', 'substances'));
    }

    public function search(Request $request)
    {
        // Get selected substance from the request - only allow single substance
        $substanceId = $request->get('substances');
        
        // If substances is an array, take only the first one
        if (is_array($substanceId)) {
            $substanceId = !empty($substanceId) ? $substanceId[0] : null;
        }
        
        // If no substance selected, redirect back to filter
        if (empty($substanceId)) {
            return redirect()->route('factsheets.search.filter')
                ->with('error', 'Please select exactly one substance to view its factsheet.');
        }
        
        // Fetch the single substance
        $substance = Substance::find($substanceId);
        
        if (!$substance) {
            return redirect()->route('factsheets.search.filter')
                ->with('error', 'Selected substance not found.');
        }
        
        // Get factsheet entities for display
        $factsheetEntities = FactsheetEntity::ordered()->get();
        
        return view('factsheet.index', compact('substance', 'factsheetEntities'));
    }

}
