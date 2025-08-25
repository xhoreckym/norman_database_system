<?php

namespace App\Http\Controllers\Ecotox;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Models\Susdat\Substance;
use App\Http\Controllers\Controller;
use App\Models\Ecotox\EcotoxFinal;
use App\Models\Ecotox\EcotoxOriginal;
use App\Models\Ecotox\EcotoxHarmonised;
use App\Models\Ecotox\EcotoxComparativeTableConfig;
use App\Models\Ecotox\EcotoxComparativeTableInputValues;
use Illuminate\Support\Facades\Auth;

class EcotoxCREDEvaluationController extends Controller
{
    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        //
        return redirect()->route('ecotox.credevaluation.home.index');
    }
    
    /**
    * Show the form for creating a new resource.
    */
    public function create()
    {
        //
    }
    
    /**
    * Store a newly created resource in storage.
    */
    public function store(Request $request)
    {
        //
    }
    
    /**
    * Show the form for editing the specified resource.
    */
    public function edit(string $id)
    {
        //
    }
    
    /**
    * Update the specified resource in storage.
    */
    public function update(Request $request, string $id)
    {
        //
    }
    
    /**
    * Remove the specified resource from storage.
    */
    public function destroy(string $id)
    {
        //
    }
    
    public function filter(Request $request)
    {
        return view('ecotox.credevaluation.filter', [
            'request' => $request,
        ]);
    }
    
    public function search(Request $request)
    {
        // Initialize search parameters array to track what filters were applied
        $searchParameters = [];
        
        // Start with a base query with necessary relationships
        $resultsObjects = EcotoxFinal::with([
            'substance',
        ])->whereIn('use_study', ['y', 'Y', 'yes', 'YES', 'Yes'])->orderBy('ecotox_id', 'asc');
        
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
            return redirect()->route('ecotox.credevaluation.search.filter');
        }
        
        // Get the full request data for logging
        $main_request = $request->all();
        
        // Get total count from database entity
        $database_key = 'ecotox.ecotox';
        $resultsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records ?? 0;
        
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
                    'user_id'      => Auth::check() ? Auth::id() : null,
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
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
            ->simplePaginate(200)
            ->withQueryString();
        } else {
            // Use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
            ->paginate(200)
            ->withQueryString();
        }
        
        // Return the view with results and metadata
        return view('ecotox.credevaluation.index', [
            'resultsObjects'      => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id'        => QueryLog::orderBy('id', 'desc')->first()->id ?? 0,
            'request'             => $request,
            'searchParameters'    => $searchParameters,
        ], $main_request);
    }
    
    public function countAll(){
        DatabaseEntity::where('code', 'ecotox.ecotox')->update([
            'last_update' => EcotoxFinal::max('updated_at'),
            'number_of_records' => EcotoxFinal::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }
    
    /**
     * Get data for CRED evaluation modal
     */
    public function getModalData($recordId)
    {
        try {
            $record = EcotoxFinal::with(['substance'])
                ->where('ecotox_id', $recordId)
                ->first();
            
            if (!$record) {
                return response()->json(['error' => 'Record not found'], 404);
            }
            
            return response()->json($record);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch record data'], 500);
        }
    }
    
    /**
     * Get evaluation history for a record
     */
    public function getEvaluationHistory($recordId)
    {
        try {
            // This would typically come from a CredEvaluation model
            // For now, returning empty array as placeholder
            $history = [];
            
            return response()->json($history);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch evaluation history'], 500);
        }
    }
    
    /**
     * Save CRED evaluation
     */
    public function saveEvaluation(Request $request)
    {
        try {
            $request->validate([
                'record_id' => 'required|string',
                'reliability_score' => 'required|string',
                'use_of_study' => 'required|string',
                'comments' => 'nullable|string',
                'evaluation_date' => 'required|date',
            ]);
            
            // This would typically save to a CredEvaluation model
            // For now, just returning success as placeholder
            $evaluationData = $request->all();
            $evaluationData['evaluated_by'] = Auth::id();
            $evaluationData['evaluated_at'] = now();
            
            // TODO: Implement actual saving logic
            // CredEvaluation::create($evaluationData);
            
            return response()->json([
                'success' => true,
                'message' => 'Evaluation saved successfully',
                'data' => $evaluationData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save evaluation: ' . $e->getMessage()
            ], 500);
        }
    }
}
