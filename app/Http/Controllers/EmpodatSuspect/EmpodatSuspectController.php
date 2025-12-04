<?php

namespace App\Http\Controllers\EmpodatSuspect;

use App\Http\Controllers\Controller;
use App\Jobs\EmpodatSuspect\EmpodatSuspectCsvExportJob;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Empodat\SearchCountries;
use App\Models\Empodat\SearchMatrix;
use App\Models\EmpodatSuspect\EmpodatSuspectDataSource;
use App\Models\EmpodatSuspect\EmpodatSuspectMain;
use App\Models\List\AnalyticalMethod;
use App\Models\List\ConcentrationIndicator;
use App\Models\List\Country;
use App\Models\List\DataSourceLaboratory;
use App\Models\List\DataSourceOrganisation;
use App\Models\List\Matrix;
use App\Models\List\QualityEmpodatAnalyticalMethods;
use App\Models\List\TypeDataSource;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmpodatSuspectController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->checkModuleAccess();
    }

    /**
     * Check if user has access to the EmpodatSuspect module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $databaseEntity) {
            abort(403, 'Module not found.');
        }

        // If module is public, allow access to everyone
        if ($databaseEntity->is_public === true) {
            return;
        }

        // Module is private - check if user is logged in
        if (! Auth::check()) {
            abort(403, 'You must be logged in to access this module.');
        }

        $user = Auth::user();

        // Always allow admin and super_admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return;
        }

        // Check if user has the specific module role
        if ($user->hasRole('empodat_suspect')) {
            return;
        }

        // User doesn't have permission
        abort(403, 'You do not have permission to access this module.');
    }

    /**
     * Show the filter form for Empodat Suspect search
     * Mimics the Empodat filter functionality
     */
    public function filter(Request $request)
    {
        // Get countries that have empodat_suspect data (via the materialized view)
        $countries = SearchCountries::with('country')
            ->whereIn('country_id', function ($query) {
                $query->select('country_id')
                    ->from('empodat_suspect_station_filters')
                    ->distinct();
            })
            ->orderBy('country_id', 'asc')
            ->get();

        $countryList = [];
        foreach ($countries as $s) {
            $countryList[$s->country_id] = $s->country->name;
        }

        // Get matrices that have empodat_suspect data (via the materialized view)
        $matrices = SearchMatrix::with('matrix')
            ->whereIn('matrix_id', function ($query) {
                $query->select('matrix_id')
                    ->from('empodat_suspect_station_filters')
                    ->distinct();
            })
            ->orderBy('matrix_id', 'asc')
            ->get();

        $matrixList = [];
        foreach ($matrices as $s) {
            $matrixList[$s->matrix_id] = $s->matrix->name;
        }

        // Get SLE sources
        $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
        $sourceList = [];
        foreach ($sources as $s) {
            $code = preg_replace('/[^a-zA-Z0-9]/', '', $s->code);
            $name = preg_replace('/[^a-zA-Z0-9]/', '', $s->name);
            $sourceList[$s->id] = $code.' - '.$name;
        }

        // Get categories
        $categories = Category::orderBy('name', 'asc')
            ->select('id', 'name', 'abbreviation')
            ->get()
            ->keyBy('id');

        // Get type data sources
        $typeDataSourcesList = [];
        $typeSources = TypeDataSource::all();
        foreach ($typeSources as $s) {
            $typeDataSourcesList[$s->id] = $s->name;
        }

        // Get concentration indicators
        $concentrationIndicatorList = [];
        $concentrationIndicator = ConcentrationIndicator::all();
        foreach ($concentrationIndicator as $s) {
            $concentrationIndicatorList[$s->id] = $s->name;
        }

        // Get analytical methods
        $analyticalMethodsList = [];
        $analyticalMethods = AnalyticalMethod::all();
        foreach ($analyticalMethods as $s) {
            $analyticalMethodsList[$s->id] = $s->name;
        }

        // Get quality analytical methods
        $qualityAnalyticalMethodsList = [];
        $qualityAnalyticalMethods = QualityEmpodatAnalyticalMethods::all();
        foreach ($qualityAnalyticalMethods as $method) {
            $qualityAnalyticalMethodsList[$method->id] = $method->name;
        }

        // Get data source laboratories
        $dataSourceLaboratoryList = [];
        $dataSourceLaboratories = DataSourceLaboratory::all();
        foreach ($dataSourceLaboratories as $laboratory) {
            $dataSourceLaboratoryList[$laboratory->id] = $laboratory->name;
        }

        // Get data source organisations
        $dataSourceOrganisationList = [];
        $dataSourceOrganisations = DataSourceOrganisation::all();
        foreach ($dataSourceOrganisations as $organisation) {
            $dataSourceOrganisationList[$organisation->id] = $organisation->name;
        }

        // Get files for empodat_suspect (database_entity_id = 18)
        // Only for admin, super_admin, and empodat_suspect roles
        $fileList = [];
        $showFileFilter = false;

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole(['admin', 'super_admin', 'empodat_suspect'])) {
                $showFileFilter = true;
                $files = \App\Models\Backend\File::where('database_entity_id', 18)
                    ->orderBy('id', 'asc')
                    ->get();
                foreach ($files as $file) {
                    $fileList[$file->id] = $file->name;
                }
            }
        }

        // Confidence level options
        $confidenceLevelList = [
            1 => 'IP_max > 0.75 AND <= 1.00',
            2 => 'IP_max > 0.60 AND <= 0.75',
            3 => 'IP_max > 0.50 AND <= 0.60',
            4 => 'IP_max > 0.20 AND <= 0.50',
            5 => 'IP_max <= 0.20',
        ];

        return view('empodat_suspect.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'matrixList' => $matrixList,
            'sourceList' => $sourceList,
            'categories' => $categories,
            'analyticalMethodsList' => $analyticalMethodsList,
            'concentrationIndicatorList' => $concentrationIndicatorList,
            'dataSourceLaboratoryList' => $dataSourceLaboratoryList,
            'typeDataSourcesList' => $typeDataSourcesList,
            'qualityAnalyticalMethodsList' => $qualityAnalyticalMethodsList,
            'dataSourceOrganisationList' => $dataSourceOrganisationList,
            'fileList' => $fileList,
            'showFileFilter' => $showFileFilter,
            'confidenceLevelList' => $confidenceLevelList,
        ]);
    }

    /**
     * Search Empodat Suspect data using the materialized view
     * Mimics the Empodat search functionality
     */
    public function search(Request $request)
    {
        try {
            // Set database timeout
            try {
                DB::statement('SET statement_timeout = 300000'); // 5 minutes timeout
            } catch (\Exception $timeoutError) {
                Log::warning('Database timeout setting not supported: '.$timeoutError->getMessage());
            }

            // Define search fields with their default values
            $searchFields = [
                'countrySearch' => [],
                'matrixSearch' => [],
                'sourceSearch' => [],
                'analyticalMethodSearch' => [],
                'categoriesSearch' => [],
                'typeDataSourcesSearch' => [],
                'concentrationIndicatorSearch' => [],
                'qualityAnalyticalMethodsSearch' => [],
                'dataSourceLaboratorySearch' => [],
                'dataSourceOrganisationSearch' => [],
                'fileSearch' => [],
                'confidenceLevelSearch' => [],
                'year_from' => null,
                'year_to' => null,
            ];

            // Process all search inputs
            $searchInputs = $this->processSearchInput($request, $searchFields);

            // STEP 1: Determine if we should use the materialized view
            // The materialized view contains station/country/matrix/year combinations
            // for fast filtering by geography and ecosystem.
            $hasStationFilters = ! empty($searchInputs['countrySearch'])
                || ! empty($searchInputs['matrixSearch'])
                || ! empty($searchInputs['year_from'])
                || ! empty($searchInputs['year_to']);

            $empodatSuspects = EmpodatSuspectMain::query()
                ->select('empodat_suspect_main.*')
                ->whereNotNull('empodat_suspect_main.station_id')
                ->whereNotNull('empodat_suspect_main.substance_id')
                ->selectSub(function ($query) {
                    $query->select('sampling_date_year')
                        ->from('empodat_suspect_station_filters')
                        ->whereColumn('empodat_suspect_station_filters.station_id', 'empodat_suspect_main.station_id')
                        ->limit(1);
                }, 'sampling_year');

            // Only use materialized view if there are station-level filters
            if ($hasStationFilters) {
                $stationFiltersQuery = DB::table('empodat_suspect_station_filters');

                // Apply filters to the materialized view
                if (! empty($searchInputs['countrySearch'])) {
                    $stationFiltersQuery->whereIn('country_id', $searchInputs['countrySearch']);
                }

                if (! empty($searchInputs['matrixSearch'])) {
                    $stationFiltersQuery->whereIn('matrix_id', $searchInputs['matrixSearch']);
                }

                if (! empty($searchInputs['year_from'])) {
                    $stationFiltersQuery->where('sampling_date_year', '>=', $searchInputs['year_from']);
                }

                if (! empty($searchInputs['year_to'])) {
                    $stationFiltersQuery->where('sampling_date_year', '<=', $searchInputs['year_to']);
                }

                // Get distinct station_ids from the filtered materialized view
                $filteredStationIds = $stationFiltersQuery->distinct()->pluck('station_id');

                // Filter empodat_suspect_main by station IDs
                $empodatSuspects->whereIn('station_id', $filteredStationIds);
            }

            // Apply additional filters specific to empodat_suspect_main
            if (! empty($request->input('substances'))) {
                $empodatSuspects->whereIn('substance_id', $request->input('substances'));
            }

            // Apply file filter (only for authorized users)
            if (! empty($searchInputs['fileSearch'])) {
                $empodatSuspects->whereIn('file_id', $searchInputs['fileSearch']);
            }

            // Apply category filter (via substance relationship)
            if (! empty($searchInputs['categoriesSearch'])) {
                $empodatSuspects->whereHas('substance.categories', function ($q) use ($searchInputs) {
                    $q->whereIn('susdat_categories.id', $searchInputs['categoriesSearch']);
                });
            }

            // Apply SLE source filter (via substance relationship)
            if (! empty($searchInputs['sourceSearch'])) {
                $empodatSuspects->whereHas('substance', function ($q) use ($searchInputs) {
                    $q->whereHas('sources', function ($sourceQuery) use ($searchInputs) {
                        $sourceQuery->whereIn('sle_sources.id', $searchInputs['sourceSearch']);
                    });
                });
            }

            // Apply confidence level filter (ip_max range) - supports multiple selections
            if (! empty($searchInputs['confidenceLevelSearch'])) {
                $empodatSuspects->where(function ($query) use ($searchInputs) {
                    foreach ($searchInputs['confidenceLevelSearch'] as $index => $level) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $query->$method(function ($q) use ($level) {
                            switch ($level) {
                                case '1': // IP_max > 0.75 AND <= 1.00
                                    $q->where('ip_max', '>', 0.75)->where('ip_max', '<=', 1.00);
                                    break;
                                case '2': // IP_max > 0.60 AND <= 0.75
                                    $q->where('ip_max', '>', 0.60)->where('ip_max', '<=', 0.75);
                                    break;
                                case '3': // IP_max > 0.50 AND <= 0.60
                                    $q->where('ip_max', '>', 0.50)->where('ip_max', '<=', 0.60);
                                    break;
                                case '4': // IP_max > 0.20 AND <= 0.50
                                    $q->where('ip_max', '>', 0.20)->where('ip_max', '<=', 0.50);
                                    break;
                                case '5': // IP_max <= 0.20
                                    $q->where('ip_max', '<=', 0.20);
                                    break;
                            }
                        });
                    }
                });
            }

            // Build search parameters for display
            $searchParameters = $this->buildSearchParameters($searchInputs, $request);

            // Prepare request data for logging
            $mainRequest = $this->prepareRequestData($request, $searchInputs);

            // Log query if not paginated request
            $queryLogId = $this->logQuery($empodatSuspects, $mainRequest, $request);

            // Apply pagination
            $empodatSuspects = $this->applyPagination($empodatSuspects, $request);

            // Eager load relationships
            $empodatSuspects->load([
                'substance',
                'station.country',
            ]);

            // Get total count
            $empodatSuspectsCount = $this->getDatabaseEntityCount('empodat_suspect');

            return view('empodat_suspect.index', array_merge([
                'empodatSuspects' => $empodatSuspects,
                'empodatSuspectsCount' => $empodatSuspectsCount,
                'query_log_id' => $queryLogId,
                'searchParameters' => $searchParameters,
            ], $mainRequest));

        } catch (\Exception $e) {
            Log::error('Empodat Suspect search failed: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('empodat_suspect.search.filter')
                ->with('error', 'Search failed due to a database error. Please try again with fewer filters or contact support.');
        }
    }

    /**
     * Process search inputs from request, handling both array and JSON string formats
     */
    private function processSearchInput(Request $request, array $fields): array
    {
        $processed = [];

        foreach ($fields as $field => $defaultValue) {
            $value = $request->input($field);

            if (is_null($value)) {
                $processed[$field] = $defaultValue;
            } elseif (is_array($value)) {
                $processed[$field] = $value;
            } else {
                // For simple string values (like year), use the value directly
                // Only try JSON decoding for fields that might contain JSON arrays
                if (str_ends_with($field, 'Search') || str_ends_with($field, '[]')) {
                    $decoded = json_decode($value, true);
                    $processed[$field] = $decoded ?? $defaultValue;
                } else {
                    $processed[$field] = $value;
                }
            }
        }

        return $processed;
    }

    /**
     * Build search parameters for display in the view
     */
    private function buildSearchParameters(array $searchInputs, Request $request): array
    {
        $searchParameters = [];

        // Country parameters
        if (! empty($searchInputs['countrySearch'])) {
            $searchParameters['countrySearch'] = Country::whereIn('id', $searchInputs['countrySearch'])->pluck('name');
        }

        // Matrix parameters
        if (! empty($searchInputs['matrixSearch'])) {
            $searchParameters['matrixSearch'] = Matrix::whereIn('id', $searchInputs['matrixSearch'])->pluck('name');
        }

        // Substance parameters
        if (! empty($request->input('substances'))) {
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
        }

        // Data source parameters
        if (! empty($searchInputs['typeDataSourcesSearch'])) {
            $searchParameters['typeDataSourcesSearch'] = TypeDataSource::whereIn('id', $searchInputs['typeDataSourcesSearch'])->pluck('name');
        }

        if (! empty($searchInputs['dataSourceLaboratorySearch'])) {
            $searchParameters['dataSourceLaboratorySearch'] = DataSourceLaboratory::whereIn('id', $searchInputs['dataSourceLaboratorySearch'])->pluck('name');
        }

        if (! empty($searchInputs['dataSourceOrganisationSearch'])) {
            $searchParameters['dataSourceOrganisationSearch'] = DataSourceOrganisation::whereIn('id', $searchInputs['dataSourceOrganisationSearch'])->pluck('name');
        }

        // Analytical method parameters
        if (! empty($searchInputs['analyticalMethodSearch'])) {
            $searchParameters['analyticalMethodSearch'] = AnalyticalMethod::whereIn('id', $searchInputs['analyticalMethodSearch'])->pluck('name');
        }

        // Category parameters
        if (! empty($searchInputs['categoriesSearch'])) {
            $searchParameters['categoriesSearch'] = Category::whereIn('id', $searchInputs['categoriesSearch'])->pluck('name');
        }

        // Concentration indicator parameters
        if (! empty($searchInputs['concentrationIndicatorSearch'])) {
            $searchParameters['concentrationIndicatorSearch'] = ConcentrationIndicator::whereIn('id', $searchInputs['concentrationIndicatorSearch'])->pluck('name');
        }

        // Source parameters
        if (! empty($searchInputs['sourceSearch'])) {
            $searchParameters['sourceSearch'] = SuspectListExchangeSource::whereIn('id', $searchInputs['sourceSearch'])->pluck('code');
        }

        // Quality parameters
        if (! empty($searchInputs['qualityAnalyticalMethodsSearch'])) {
            $searchParameters['ratings'] = QualityEmpodatAnalyticalMethods::whereIn('id', $searchInputs['qualityAnalyticalMethodsSearch'])->get();
        }

        // Year parameters
        if (! is_null($request->input('year_from'))) {
            $searchParameters['year_from'] = $request->input('year_from');
        }

        if (! is_null($request->input('year_to'))) {
            $searchParameters['year_to'] = $request->input('year_to');
        }

        // File parameters (only for authorized users)
        if (! empty($searchInputs['fileSearch'])) {
            $searchParameters['fileSearch'] = \App\Models\Backend\File::whereIn('id', $searchInputs['fileSearch'])->pluck('name');
        }

        // Confidence level parameter
        if (! empty($searchInputs['confidenceLevelSearch'])) {
            $confidenceLevels = [
                '1' => 'IP_max > 0.75 AND <= 1.00',
                '2' => 'IP_max > 0.60 AND <= 0.75',
                '3' => 'IP_max > 0.50 AND <= 0.60',
                '4' => 'IP_max > 0.20 AND <= 0.50',
                '5' => 'IP_max <= 0.20',
            ];
            $selectedLevels = [];
            foreach ($searchInputs['confidenceLevelSearch'] as $level) {
                if (isset($confidenceLevels[$level])) {
                    $selectedLevels[] = $confidenceLevels[$level];
                }
            }
            $searchParameters['confidenceLevelSearch'] = collect($selectedLevels);
        }

        return $searchParameters;
    }

    /**
     * Prepare request data for logging
     */
    private function prepareRequestData(Request $request, array $searchInputs): array
    {
        $requestData = array_merge($searchInputs, [
            'year_from' => $request->input('year_from'),
            'year_to' => $request->input('year_to'),
            'displayOption' => $request->input('displayOption'),
            'substances' => $request->input('substances'),
        ]);

        return $requestData;
    }

    /**
     * Log the query for analytics and caching
     */
    private function logQuery($query, array $mainRequest, Request $request): ?int
    {
        if ($request->has('page')) {
            return QueryLog::orderBy('id', 'desc')->first()?->id;
        }

        $databaseKey = 'empodat_suspect';
        $empodatSuspectsCount = $this->getDatabaseEntityCount($databaseKey);
        $now = now();
        $bindings = $query->getBindings();
        $sql = vsprintf(str_replace('?', "'%s'", $query->toSql()), $bindings);
        $queryHash = hash('sha256', $sql);

        // Check for existing query with same hash
        $actualCount = QueryLog::where('query_hash', $queryHash)
            ->where('total_count', $empodatSuspectsCount)
            ->value('actual_count');

        try {
            QueryLog::insert([
                'content' => json_encode(['request' => $mainRequest, 'bindings' => $bindings]),
                'query' => $sql,
                'user_id' => Auth::id(),
                'total_count' => $empodatSuspectsCount,
                'actual_count' => $actualCount,
                'database_key' => $databaseKey,
                'query_hash' => $queryHash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return QueryLog::orderBy('id', 'desc')->first()->id;

        } catch (\Exception $e) {
            Log::error('Query logging failed: '.$e->getMessage(), [
                'query_hash' => $queryHash,
                'user_id' => Auth::id(),
            ]);

            session()->flash('error', 'An error occurred while processing your request.');

            return null;
        }
    }

    /**
     * Apply pagination based on display option
     */
    private function applyPagination($query, Request $request)
    {
        $orderBy = $query->orderBy('empodat_suspect_main.id', 'asc');

        if ($request->input('displayOption') == 1) {
            return $orderBy->simplePaginate(200)->withQueryString();
        } else {
            return $orderBy->paginate(200)->withQueryString();
        }
    }

    /**
     * Get database entity record count
     */
    private function getDatabaseEntityCount(string $databaseKey): int
    {
        return DatabaseEntity::where('code', $databaseKey)->value('number_of_records') ?? 0;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $record = EmpodatSuspectMain::with([
            'substance',
            'station.country',
            'xlsxStationMapping',
            'file',
        ])->findOrFail($id);

        // Get data source information via file_id
        $dataSource = null;
        if ($record->file_id) {
            $dataSource = EmpodatSuspectDataSource::with([
                'sourceData',
                'monitoringType',
                'organisation.country',
                'laboratory.country',
            ])->where('file_id', $record->file_id)->first();
        }

        // Get matrix metadata from pre-computed MVs
        // NOTE: We pick only the FIRST matching record per matrix type
        // because one station can have many empodat_main records
        $matrixMetadata = [];
        $stationId = $record->station_id;

        if ($stationId) {
            // Check each matrix MV for data
            $matrixTypes = [
                'biota' => 'empodat_suspect_matrix_biota',
                'sediments' => 'empodat_suspect_matrix_sediments',
                'water_surface' => 'empodat_suspect_matrix_water_surface',
                'water_ground' => 'empodat_suspect_matrix_water_ground',
                'water_waste' => 'empodat_suspect_matrix_water_waste',
                'suspended_matter' => 'empodat_suspect_matrix_suspended_matter',
                'soil' => 'empodat_suspect_matrix_soil',
                'air' => 'empodat_suspect_matrix_air',
                'sewage_sludge' => 'empodat_suspect_matrix_sewage_sludge',
            ];

            foreach ($matrixTypes as $type => $tableName) {
                try {
                    $data = DB::table($tableName)
                        ->where('station_id', $stationId)
                        ->first();

                    if ($data) {
                        $matrixMetadata[$type] = $data;
                    }
                } catch (\Exception $e) {
                    // MV might not exist yet, skip silently
                }
            }
        }

        return view('empodat_suspect.show', [
            'record' => $record,
            'matrixMetadata' => $matrixMetadata,
            'dataSource' => $dataSource,
        ]);
    }

    /**
     * Start CSV download job (dispatches to queue for background processing)
     *
     * Uses the queue-based EmpodatSuspectCsvExportJob which:
     * - Processes large exports in the background
     * - Includes all matrix metadata (biota, sediments, water, soil, air, etc.)
     * - Sends email notification when complete
     * - Tracks progress in the ExportDownload table
     */
    public function startDownloadJob($query_log_id)
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        // Generate filename at dispatch time to avoid timing issues
        $filename = 'empodat_suspect_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

        // Dispatch the job to the queue
        $user = Auth::user();
        EmpodatSuspectCsvExportJob::dispatch($query_log_id, $user, $filename);

        session()->flash('success', 'The CSV file is being generated with all matrix metadata. You will receive an email once it is ready for download, or check the "My downloads" page for the status.');

        return back();
    }

    /**
     * Download CSV file
     */
    public function downloadCsv($filename)
    {
        $directory = 'exports/empodat_suspect';
        $path = Storage::path("{$directory}/{$filename}");

        if (! file_exists($path)) {
            return response()->json([
                'error' => 'File not found',
                'message' => 'The requested CSV file does not exist.',
            ], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Format bytes to human-readable string
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
