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
use Illuminate\Support\Facades\Auth;

class EcotoxController extends Controller
{
    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        //
        return redirect()->route('ecotox.home.index');
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Fetch data from all three Ecotox models for the given ecotox_id
        $ecotoxFinal = EcotoxFinal::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();
            
        $ecotoxOriginal = EcotoxOriginal::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();
            
        $ecotoxHarmonised = EcotoxHarmonised::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();

        if (!$ecotoxFinal && !$ecotoxOriginal && !$ecotoxHarmonised) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        // Prepare data structure for the table display
        $tableData = [
            // Source section
            'Source' => [
                'Ecotox DS ID' => [
                    'original' => $ecotoxOriginal?->data_source_id ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->data_source_id ?? 'N/A',
                    'final' => $ecotoxFinal?->data_source_id ?? 'N/A'
                ],
                'Biotest ID' => [
                    'original' => $ecotoxOriginal?->ecotox_id ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->ecotox_id ?? 'N/A',
                    'final' => $ecotoxFinal?->ecotox_id ?? 'N/A'
                ],
                'Data source' => [
                    'original' => $ecotoxOriginal?->data_source ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->data_source ?? 'N/A',
                    'final' => $ecotoxFinal?->data_source ?? 'N/A'
                ],
                'Data source ID' => [
                    'original' => $ecotoxOriginal?->data_source_id ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->data_source_id ?? 'N/A',
                    'final' => $ecotoxFinal?->data_source_id ?? 'N/A'
                ],
                'Data source reference ID' => [
                    'original' => $ecotoxOriginal?->data_source_ref ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->data_source_ref ?? 'N/A',
                    'final' => $ecotoxFinal?->data_source_ref ?? 'N/A'
                ],
                'Data protection' => [
                    'original' => $ecotoxOriginal?->data_protection ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->data_protection ?? 'N/A',
                    'final' => $ecotoxFinal?->data_protection ?? 'N/A'
                ],
                'Data source link' => [
                    'original' => $ecotoxOriginal?->data_source_link ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->data_source_link ?? 'N/A',
                    'final' => $ecotoxFinal?->data_source_link ?? 'N/A'
                ],
                'Editor' => [
                    'original' => $ecotoxOriginal?->edit_editor ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->edit_editor ?? 'N/A',
                    'final' => $ecotoxFinal?->edit_editor ?? 'N/A'
                ],
                'Use of study' => [
                    'original' => $ecotoxOriginal?->use_study ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->use_study ?? 'N/A',
                    'final' => $ecotoxFinal?->use_study ?? 'N/A'
                ],
                'Date' => [
                    'original' => $ecotoxOriginal?->edit_date ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->edit_date ?? 'N/A',
                    'final' => $ecotoxFinal?->edit_date ?? 'N/A'
                ]
            ],
            // Reference section
            'Reference' => [
                'Study title' => [
                    'original' => $ecotoxOriginal?->study_title ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->study_title ?? 'N/A',
                    'final' => $ecotoxFinal?->study_title ?? 'N/A'
                ],
                'Authors' => [
                    'original' => $ecotoxOriginal?->authors ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->authors ?? 'N/A',
                    'final' => $ecotoxFinal?->authors ?? 'N/A'
                ],
                'Year publication' => [
                    'original' => $ecotoxOriginal?->year_publication ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->year_publication ?? 'N/A',
                    'final' => $ecotoxFinal?->year_publication ?? 'N/A'
                ],
                'Bibliographic source' => [
                    'original' => $ecotoxOriginal?->bibliographic_source ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->bibliographic_source ?? 'N/A',
                    'final' => $ecotoxFinal?->bibliographic_source ?? 'N/A'
                ]
            ],
            // Test section
            'Test' => [
                'Test type' => [
                    'original' => $ecotoxOriginal?->test_type ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->test_type ?? 'N/A',
                    'final' => $ecotoxFinal?->test_type ?? 'N/A'
                ],
                'Acute or chronic' => [
                    'original' => $ecotoxOriginal?->acute_or_chronic ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->acute_or_chronic ?? 'N/A',
                    'final' => $ecotoxFinal?->acute_or_chronic ?? 'N/A'
                ],
                'Endpoint' => [
                    'original' => $ecotoxOriginal?->endpoint ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->endpoint ?? 'N/A',
                    'final' => $ecotoxFinal?->endpoint ?? 'N/A'
                ],
                'Duration' => [
                    'original' => $ecotoxOriginal?->duration ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->duration ?? 'N/A',
                    'final' => $ecotoxFinal?->duration ?? 'N/A'
                ],
                'Effect measurement' => [
                    'original' => $ecotoxOriginal?->effect_measurement ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->effect_measurement ?? 'N/A',
                    'final' => $ecotoxFinal?->effect_measurement ?? 'N/A'
                ],
                'Effect' => [
                    'original' => $ecotoxOriginal?->effect ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->effect ?? 'N/A',
                    'final' => $ecotoxFinal?->effect ?? 'N/A'
                ]
            ],
            // Organism section
            'Organism' => [
                'Scientific name' => [
                    'original' => $ecotoxOriginal?->scientific_name ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->scientific_name ?? 'N/A',
                    'final' => $ecotoxFinal?->scientific_name ?? 'N/A'
                ],
                'Common name' => [
                    'original' => $ecotoxOriginal?->common_name ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->common_name ?? 'N/A',
                    'final' => $ecotoxFinal?->common_name ?? 'N/A'
                ],
                'Taxonomic group' => [
                    'original' => $ecotoxOriginal?->taxonomic_group ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->taxonomic_group ?? 'N/A',
                    'final' => $ecotoxFinal?->taxonomic_group ?? 'N/A'
                ]
            ],
            // Concentration section
            'Concentration' => [
                'Concentration value' => [
                    'original' => $ecotoxOriginal?->concentration_value ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->concentration_value ?? 'N/A',
                    'final' => $ecotoxFinal?->concentration_value ?? 'N/A'
                ],
                'Concentration qualifier' => [
                    'original' => $ecotoxOriginal?->concentration_qualifier ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->concentration_qualifier ?? 'N/A',
                    'final' => $ecotoxFinal?->concentration_qualifier ?? 'N/A'
                ],
                'Unit concentration' => [
                    'original' => $ecotoxOriginal?->unit_concentration ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->unit_concentration ?? 'N/A',
                    'final' => $ecotoxFinal?->unit_concentration ?? 'N/A'
                ]
            ],
            // Additional fields section
            'Additional' => [
                'Matrix habitat' => [
                    'original' => $ecotoxOriginal?->matrix_habitat ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->matrix_habitat ?? 'N/A',
                    'final' => $ecotoxFinal?->matrix_habitat ?? 'N/A'
                ],
                'Testing laboratory' => [
                    'original' => $ecotoxOriginal?->testing_laboratory ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->testing_laboratory ?? 'N/A',
                    'final' => $ecotoxFinal?->testing_laboratory ?? 'N/A'
                ],
                'GLP certificate' => [
                    'original' => $ecotoxOriginal?->glp_certificate ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->glp_certificate ?? 'N/A',
                    'final' => $ecotoxFinal?->glp_certificate ?? 'N/A'
                ],
                'Reliability study' => [
                    'original' => $ecotoxOriginal?->reliability_study ?? 'N/A',
                    'harmonised' => $ecotoxHarmonised?->reliability_study ?? 'N/A',
                    'final' => $ecotoxFinal?->reliability_study ?? 'N/A'
                ]
            ]
        ];

        return response()->json([
            'ecotox_id' => $id,
            'substance' => $ecotoxFinal?->substance ?? $ecotoxOriginal?->substance ?? $ecotoxHarmonised?->substance,
            'table_data' => $tableData
        ]);
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
        return view('ecotox.filter', [
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
        ]);
        
        // Apply substance filter (this is the primary filter)
        if (!empty($request->input('substances'))) {
            $substances = $request->input('substances');
            // Handle case when substances is a string (JSON)
            if (is_string($substances)) {
                $substances = json_decode($substances, true);
            }
            
            $resultsObjects = $resultsObjects->whereIn('substance_id', $substances);
            $searchParameters['substances'] = Substance::whereIn('id', $substances)->pluck('name');
        } else {
            // If no substances specified, merge empty array to avoid errors
            $request->merge(['substances' => []]);
            // Return early as we require at least one substance
            session()->flash('info', 'Please select at least one substance to search.');
            return redirect()->route('ecotox.search.filter');
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
        return view('ecotox.index', [
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
}
