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

        // Helper function to get value from models
        $getValue = function($field) use ($ecotoxOriginal, $ecotoxHarmonised, $ecotoxFinal) {
            return [
                'original' => $ecotoxOriginal?->$field ?? 'N/A',
                'harmonised' => $ecotoxHarmonised?->$field ?? 'N/A',
                'final' => $ecotoxFinal?->$field ?? 'N/A'
            ];
        };

        // Prepare data structure for the table display based on the column mapping
        $tableData = [
            'Source' => [
                'Ecotox DS ID' => $getValue('ecotox_id'),
                'Biotest ID' => $getValue('biotest_id'),
                'Data source' => $getValue('data_source'),
                'Data source ID' => $getValue('data_source_id'),
                'Data source reference ID' => $getValue('data_source_ref'),
                'Data protection' => $getValue('data_protection'),
                'Data source link' => $getValue('data_source_link'),
                'Editor' => $getValue('edit_editor'),
                'Use of study' => $getValue('use_study'),
                'Date' => $getValue('edit_date'),
            ],
            'Reference' => [
                'Reference type' => $getValue('reference_type'),
                'Reference ID' => $getValue('reference_id'),
                'Title' => $getValue('study_title'),
                'Author(s)' => $getValue('authors'),
                'Year' => $getValue('year_publication'),
                'Bibliographic source' => $getValue('bibliographic_source'),
            ],
            'Categorisation' => [
                'Compartment' => $getValue('matrix_habitat'),
                'Test type' => $getValue('test_type'),
                'Acute/Chronic' => $getValue('acute_or_chronic'),
                'Standard test' => $getValue('standard_test'),
            ],
            'Test substance' => [
                'Substance name' => $getValue('substance_name'),
                'CAS Number' => $getValue('cas_number'),
                'EC Number' => $getValue('ec_number'),
                'Purity [%] of test item' => $getValue('purity'),
                'Supplier of test item' => $getValue('supplier'),
                'Vehicle substance' => $getValue('vehicle_substance'),
                'Concentrations of vehicle or impurities' => $getValue('known_concentrations'),
                'Radio labeled substance' => $getValue('radio_substance'),
                'Preparation of stock solutions' => $getValue('preparation_solutions'),
            ],
            'Biotest' => [
                'Standard qualifier' => $getValue('standard_qualifier'),
                'Standard used' => $getValue('standard_used'),
                'Principles of method if other than guideline' => $getValue('principles'),
                'GLP Certificate' => $getValue('glp_certificate'),
                'Effect' => $getValue('effect'),
                'Effect measurement' => $getValue('effect_measurement'),
                'Endpoint' => $getValue('endpoint'),
                'Response site' => $getValue('response_site'),
                'Test duration' => $getValue('duration'),
                'Recovery considered?' => $getValue('recovery_considered'),
            ],
            'Test Organism' => [
                'Scientific name' => $getValue('scientific_name'),
                'Common name' => $getValue('common_name'),
                'Taxonomic group' => $getValue('taxonomic_group'),
                'Final body length of control' => $getValue('final_body_length_of_control'),
                'Final body weight of control' => $getValue('final_body_weight_of_control'),
                'Initial cell density' => $getValue('initial_cell_density'),
                'Final cell density' => $getValue('final_cell_density'),
                'Deformed or abnormal cells / organism' => $getValue('deformed_or_abnormal_cells'),
                'Reproductive condition of the control' => $getValue('reproductive_condition'),
                'Lipid %' => $getValue('lipid'),
                'Age' => $getValue('age'),
                'Life stage' => $getValue('life_stage'),
                'Gender' => $getValue('gender'),
                'Strain, clone' => $getValue('strain_clone'),
                'Source (laboratory, culture collection)' => $getValue('organism_source'),
                'Culture handling' => $getValue('culture_handling'),
                'Acclimation' => $getValue('acclimation'),
            ],
            'Dosing system' => [
                'Nominal concentrations' => $getValue('nominal_concentrations'),
                'Concentration Unit' => $getValue('unit_concentration'),
                'Measured or nominal concentrations' => $getValue('measured_or_nominal'),
                'Limit test' => $getValue('limit_test'),
                'Range finding study' => $getValue('range_finding_study'),
                'Analytical matrix' => $getValue('analytical_matrix'),
                'Analytical schedule' => $getValue('analytical_schedule'),
                'Analytical method' => $getValue('analytical_method'),
                'Analytical recovery' => $getValue('analytical_recovery'),
                'Limit of quantification' => $getValue('limit_of_quantification'),
                'Exposure regime' => $getValue('exposure_regime'),
                'Exposure route' => $getValue('exposure_route'),
                'Exposure duration' => $getValue('exposure_duration'),
                'Total test duration' => $getValue('total_test_duration'),
                'Application frequency' => $getValue('application_freq'),
            ],
            'Controls and Study design' => [
                'Negative control used?' => $getValue('negative_control_used'),
                'Positive control used?' => $getValue('positive_control_used'),
                'Positive control substance' => $getValue('positive_control_substance'),
                'Other effects' => $getValue('other_effects'),
                'Effects in positive control' => $getValue('effects_control'),
                'Vehicle control used?' => $getValue('vehicle_control'),
                'Effects in vehicle control' => $getValue('effects_vehicle'),
            ],
            'Test conditions' => [
                'Intervals of water quality measurements' => $getValue('intervals_water'),
                'pH' => $getValue('ph'),
                'Adjustment of pH' => $getValue('adjustment_ph'),
                'Temperature' => $getValue('temperature'),
                'Conductivity' => $getValue('conductivity'),
                'Light intensity' => $getValue('light_intensity'),
                'Light quality (source and homogeneity)' => $getValue('light_quality'),
                'Photo period' => $getValue('photo_period'),
                'Hardness' => $getValue('hardness'),
                'Chlorine' => $getValue('chlorine'),
                'Alkalinity' => $getValue('alkalinity'),
                'Salinity' => $getValue('salinity'),
                'Total Organic Carbon' => $getValue('organic_carbon'),
                'Dissolved oxygen' => $getValue('dissolved_oxygen'),
                'Material of test vessel' => $getValue('material_vessel'),
                'Volume of test vessel' => $getValue('volume_vessel'),
                'Open or closed system' => $getValue('open_closed'),
                'Aeration' => $getValue('aeration'),
                'Description of test medium' => $getValue('description_medium'),
                'Culture medium different from test medium?' => $getValue('culture_medium'),
                'Feeding protocols' => $getValue('feeding_protocols'),
                'Type and amount of food' => $getValue('type_amount_food'),
            ],
            'Statistical design' => [
                'Number of organisms per replicate' => $getValue('number_organisms'),
                'Number of replicates per concentration' => $getValue('number_replicates'),
                'Statistical method used' => $getValue('statistical_method'),
                'Trend' => $getValue('trend'),
                'Significance of result' => $getValue('significance_result'),
                'Significance level' => $getValue('significance_level'),
            ],
            'Biological effect' => [
                'Effect concentration qualifier' => $getValue('concentration_qualifier'),
                'Effect concentration' => $getValue('concentration_value'),
                'Effect concentration unit' => $getValue('unit_concentration'),
                'Estimate of variability for LC and EC data' => $getValue('estimate_variability'),
                'Test item' => $getValue('test_item'),
                'Result comment' => $getValue('result_comment'),
            ],
            'Evaluation' => [
                'Dose-response reported in figure/text/table' => $getValue('dose_response'),
                'Availability of raw data' => $getValue('availability_raw_data'),
                'Study available' => $getValue('study_available'),
                'General Comment' => $getValue('general_comment'),
                'Existing reliability score' => $getValue('reliability_study'),
                'Reliability score system used' => $getValue('reliability_score'),
                'Existing rational reliability' => $getValue('existing_rational_reliability'),
                'Regulatory context' => $getValue('regulatory_purpose'),
                'Used for regulatory purpose' => $getValue('used_for_regulaltory_purpose'),
            ],
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
