<?php

namespace App\Http\Controllers\Factsheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;

class FactsheetHomeController extends Controller
{
    public function index()
    {
        // Get factsheet database entity
        $databaseEntity = DatabaseEntity::where('code', 'factsheets')->first();
        
        // Get total count of substances with factsheet data
        $totalSubstances = DB::table('susdat_substances')
            ->where(function($query) {
                $query->whereRaw('(SELECT COUNT(*) FROM empodat_main WHERE substance_id = susdat_substances.id) > 0')
                      ->orWhereRaw('(SELECT COUNT(*) FROM ecotox_main_3 WHERE substance_id = susdat_substances.id) > 0')
                      ->orWhereRaw('(SELECT COUNT(*) FROM indoor_main WHERE substance_id = susdat_substances.id) > 0')
                      ->orWhereRaw('(SELECT COUNT(*) FROM passive_sampling_main WHERE substance_id = susdat_substances.id) > 0');
            })
            ->count();
        
        // Get module statistics
        $moduleStats = [
            'empodat' => [
                'name' => 'Chemical Occurrence Data',
                'count' => DB::table('empodat_main')->distinct('substance_id')->count('substance_id'),
                'route' => 'codsearch.filter'
            ],
            'ecotox' => [
                'name' => 'Ecotoxicology',
                'count' => DB::table('ecotox_main_3')->distinct('substance_id')->count('substance_id'),
                'route' => 'ecotox.data.search.filter'
            ],
            'indoor' => [
                'name' => 'Indoor Environment',
                'count' => DB::table('indoor_main')->distinct('substance_id')->count('substance_id'),
                'route' => 'indoor.search.filter'
            ],
            'passive' => [
                'name' => 'Passive Sampling',
                'count' => DB::table('passive_sampling_main')->distinct('substance_id')->count('substance_id'),
                'route' => 'passive.search.filter'
            ]
        ];
        
        return view('factsheet.home', compact('databaseEntity', 'totalSubstances', 'moduleStats'));
    }

    public function countAll()
    {
        // Get counts for all modules
        $counts = [
            'total_substances' => DB::table('susdat_substances')
                ->where(function($query) {
                    $query->whereRaw('(SELECT COUNT(*) FROM empodat_main WHERE substance_id = susdat_substances.id) > 0')
                          ->orWhereRaw('(SELECT COUNT(*) FROM ecotox_main_3 WHERE substance_id = susdat_substances.id) > 0')
                          ->orWhereRaw('(SELECT COUNT(*) FROM indoor_main WHERE substance_id = susdat_substances.id) > 0')
                          ->orWhereRaw('(SELECT COUNT(*) FROM passive_sampling_main WHERE substance_id = susdat_substances.id) > 0');
                })
                ->count(),
            'empodat_substances' => DB::table('empodat_main')->distinct('substance_id')->count('substance_id'),
            'ecotox_substances' => DB::table('ecotox_main_3')->distinct('substance_id')->count('substance_id'),
            'indoor_substances' => DB::table('indoor_main')->distinct('substance_id')->count('substance_id'),
            'passive_substances' => DB::table('passive_sampling_main')->distinct('substance_id')->count('substance_id'),
        ];
        
        return response()->json($counts);
    }
}
