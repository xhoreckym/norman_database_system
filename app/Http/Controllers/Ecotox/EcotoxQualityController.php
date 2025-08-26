<?php

namespace App\Http\Controllers\Ecotox;

use App\Models\Ecotox\PNEC3;
use Illuminate\Http\Request;
use App\Models\Backend\QueryLog;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\DatabaseEntity;

class EcotoxQualityController extends Controller
{
    /**
     * Show the initial quality index page.
     */
    public function index(Request $request)
    {
        return view('ecotox.quality.index', [
            'resultsObjects' => collect(),
            'matrixHabitatCounts' => collect(),
            'request' => $request,
            'searchParameters' => [],
            'resultsObjectsCount' => 0,
            'query_log_id' => null,
        ]);
    }

    /**
     * Show the search filter form.
     */
    public function filter(Request $request)
    {
        return view('ecotox.quality.filter', [
            'request' => $request,
        ]);
    }
    
    /**
     * Process the search and display results.
     */
    public function search(Request $request)
    {
        // Initialize search parameters array to track what filters were applied
        $searchParameters = [];
        
        // Start with a base query
        $resultsObjects = PNEC3::orderBy('norman_pnec_id', 'asc');
        
        // Apply substance filter (this is the primary filter)
        if (!empty($request->input('substances'))) {
            $substances = $request->input('substances');
            // Handle case when substances is a string (JSON)
            if (is_string($substances)) {
                $substances = json_decode($substances, true);
            }
            // Ensure substances is always an array
            if (!is_array($substances)) {
                $substances = [$substances];
            }
            $resultsObjects = $resultsObjects->whereIn('substance_id', $substances);
            $searchParameters['substances'] = Substance::whereIn('id', $substances)->pluck('name');
        } else {
            // If no substances specified, merge empty array to avoid errors
            $request->merge(['substances' => []]);
            // Return early as we require at least one substance
            session()->flash('info', 'Please select at least one substance to search.');
            return redirect()->route('ecotox.quality.search.filter');
        }
        
        // Get the full request data for logging
        $main_request = $request->all();
        
        // Get total count from database entity
        $database_key = 'ecotox.ecotox_pnec3';
        $resultsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records ?? 0;
        
        // Get matrix_habitat counts for the specific substance
        $substanceId = is_array($substances) ? $substances[0] : $substances;
        $matrixHabitatCounts = PNEC3::where('substance_id', $substanceId)
            ->select('matrix_habitat', DB::raw('count(*) as count'))
            ->groupBy('matrix_habitat')
            ->orderBy('matrix_habitat')
            ->get();
        
        // Log the query if this is the first page request
        if(!$request->has('page')) {
            $now = now();
            $bindings = $resultsObjects->getBindings();
            $sql = vsprintf(str_replace('?', "'%s'", $resultsObjects->toSql()), $bindings);
            
            // Try to find the same SQL query in the QueryLog table
            $actual_count = QueryLog::where('query_hash', hash('sha256', $sql))
            ->where('total_count', $resultsObjectsCount)
            ->value('actual_count');
            
            try {
                QueryLog::insert([
                    'content'      => json_encode(['request' => $main_request, 'bindings' => $bindings]),
                    'query'        => $sql,
                    'user_id'      => auth()->check() ? auth()->id() : null,
                    'total_count'  => $resultsObjectsCount,
                    'actual_count' => is_null($actual_count) ? $resultsObjects->count() : $actual_count,
                    'database_key' => $database_key,
                    'query_hash'   => hash('sha256', $sql),
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            } catch (\Exception $e) {
                if (Auth::check() && Auth::user()->hasRole('super_admin')) {
                    session()->flash('failure', 'Query logging error: ' . $e->getMessage());
                } else {
                    session()->flash('error', 'An error occurred while processing your request.');
                }
            }
        }
        
        // Apply pagination based on display option
        if ($request->input('displayOption') == 1) {
            // Use simple pagination
            $resultsObjects = $resultsObjects->orderBy('matrix_habitat')
                ->orderBy('pnec_type')
                ->orderBy('value')
                ->simplePaginate(200)
                ->withQueryString();
        } else {
            // Use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('matrix_habitat')
                ->orderBy('pnec_type')
                ->orderBy('value')
                ->paginate(200)
                ->withQueryString();
        }
        
        // Return the view with results and metadata
        return view('ecotox.quality.index', [
            'resultsObjects'      => $resultsObjects,
            'matrixHabitatCounts' => $matrixHabitatCounts,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id'        => QueryLog::orderBy('id', 'desc')->first()->id ?? 0,
            'request'             => $request,
            'searchParameters'    => $searchParameters,
        ], $main_request);
    }
}
