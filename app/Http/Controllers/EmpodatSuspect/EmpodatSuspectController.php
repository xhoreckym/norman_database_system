<?php

namespace App\Http\Controllers\EmpodatSuspect;

use App\Models\List\Matrix;
use App\Models\List\Country;
use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use App\Models\Empodat\SearchMatrix;
use App\Http\Controllers\Controller;
use App\Models\Empodat\SearchCountries;
use App\Models\List\ConcentrationIndicator;
use App\Models\EmpodatSuspect\EmpodatSuspectMain;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\List\AnalyticalMethod;
use App\Models\List\DataSourceLaboratory;
use App\Models\List\DataSourceOrganisation;
use App\Models\List\QualityEmpodatAnalyticalMethods;
use App\Models\List\TypeDataSource;
use App\Models\Backend\ExportDownload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmpodatSuspectController extends Controller
{
    /**
     * Show the filter form for Empodat Suspect search
     * Mimics the Empodat filter functionality
     */
    public function filter(Request $request)
    {
        // Get countries that have empodat_suspect data (via the materialized view)
        $countries = SearchCountries::with('country')
            ->whereIn('country_id', function($query) {
                $query->select('country_id')
                      ->from('empodat_suspect_station_filters')
                      ->distinct();
            })
            ->orderBy('country_id', 'asc')
            ->get();

        $countryList = [];
        foreach ($countries as $s) {
            $countryList[$s->country_id] = $s->country->name . ' - ' . $s->country->code;
        }

        // Get matrices that have empodat_suspect data (via the materialized view)
        $matrices = SearchMatrix::with('matrix')
            ->whereIn('matrix_id', function($query) {
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
            $sourceList[$s->id] = $code . ' - ' . $name;
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
                Log::warning('Database timeout setting not supported: ' . $timeoutError->getMessage());
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
                'year_from' => null,
                'year_to' => null,
            ];

            // Process all search inputs
            $searchInputs = $this->processSearchInput($request, $searchFields);

            // STEP 1: Query the materialized view to get filtered station IDs
            // This is the key optimization - we filter on the small materialized view first
            $stationFiltersQuery = DB::table('empodat_suspect_station_filters');

            // Apply filters to the materialized view
            if (!empty($searchInputs['countrySearch'])) {
                $stationFiltersQuery->whereIn('country_id', $searchInputs['countrySearch']);
            }

            if (!empty($searchInputs['matrixSearch'])) {
                $stationFiltersQuery->whereIn('matrix_id', $searchInputs['matrixSearch']);
            }

            if (!empty($request->input('substances'))) {
                $stationFiltersQuery->whereIn('substance_id', $request->input('substances'));
            }

            if (!empty($searchInputs['concentrationIndicatorSearch'])) {
                $stationFiltersQuery->whereIn('concentration_indicator_id', $searchInputs['concentrationIndicatorSearch']);
            }

            if (!empty($searchInputs['year_from'])) {
                $stationFiltersQuery->where('sampling_date_year', '>=', $searchInputs['year_from']);
            }

            if (!empty($searchInputs['year_to'])) {
                $stationFiltersQuery->where('sampling_date_year', '<=', $searchInputs['year_to']);
            }

            if (!empty($searchInputs['typeDataSourcesSearch'])) {
                $stationFiltersQuery->whereIn('data_source_id', $searchInputs['typeDataSourcesSearch']);
            }

            if (!empty($searchInputs['analyticalMethodSearch'])) {
                $stationFiltersQuery->whereIn('method_id', $searchInputs['analyticalMethodSearch']);
            }

            // Get distinct station_ids from the filtered materialized view
            $filteredStationIds = $stationFiltersQuery->distinct()->pluck('station_id');

            // STEP 2: Now query empodat_suspect_main with the filtered station IDs
            // This is fast because we're only querying a subset of stations
            // Add a subquery to get the sampling year from the materialized view
            $empodatSuspects = EmpodatSuspectMain::query()
                ->select('empodat_suspect_main.*')
                ->selectSub(function ($query) {
                    $query->select('sampling_date_year')
                          ->from('empodat_suspect_station_filters')
                          ->whereColumn('empodat_suspect_station_filters.station_id', 'empodat_suspect_main.station_id')
                          ->limit(1);
                }, 'sampling_year')
                ->whereIn('station_id', $filteredStationIds);

            // Apply additional filters specific to empodat_suspect_main
            if (!empty($request->input('substances'))) {
                $empodatSuspects->whereIn('substance_id', $request->input('substances'));
            }

            // Apply category filter (via substance relationship)
            if (!empty($searchInputs['categoriesSearch'])) {
                $empodatSuspects->whereHas('substance.categories', function ($q) use ($searchInputs) {
                    $q->whereIn('susdat_categories.id', $searchInputs['categoriesSearch']);
                });
            }

            // Apply SLE source filter (via substance relationship)
            if (!empty($searchInputs['sourceSearch'])) {
                $empodatSuspects->whereHas('substance', function ($q) use ($searchInputs) {
                    $q->whereHas('sources', function ($sourceQuery) use ($searchInputs) {
                        $sourceQuery->whereIn('sle_sources.id', $searchInputs['sourceSearch']);
                    });
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
            Log::error('Empodat Suspect search failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
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
        if (!empty($searchInputs['countrySearch'])) {
            $searchParameters['countrySearch'] = Country::whereIn('id', $searchInputs['countrySearch'])->pluck('name');
        }

        // Matrix parameters
        if (!empty($searchInputs['matrixSearch'])) {
            $searchParameters['matrixSearch'] = Matrix::whereIn('id', $searchInputs['matrixSearch'])->pluck('name');
        }

        // Substance parameters
        if (!empty($request->input('substances'))) {
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
        }

        // Data source parameters
        if (!empty($searchInputs['typeDataSourcesSearch'])) {
            $searchParameters['typeDataSourcesSearch'] = TypeDataSource::whereIn('id', $searchInputs['typeDataSourcesSearch'])->pluck('name');
        }

        if (!empty($searchInputs['dataSourceLaboratorySearch'])) {
            $searchParameters['dataSourceLaboratorySearch'] = DataSourceLaboratory::whereIn('id', $searchInputs['dataSourceLaboratorySearch'])->pluck('name');
        }

        if (!empty($searchInputs['dataSourceOrganisationSearch'])) {
            $searchParameters['dataSourceOrganisationSearch'] = DataSourceOrganisation::whereIn('id', $searchInputs['dataSourceOrganisationSearch'])->pluck('name');
        }

        // Analytical method parameters
        if (!empty($searchInputs['analyticalMethodSearch'])) {
            $searchParameters['analyticalMethodSearch'] = AnalyticalMethod::whereIn('id', $searchInputs['analyticalMethodSearch'])->pluck('name');
        }

        // Category parameters
        if (!empty($searchInputs['categoriesSearch'])) {
            $searchParameters['categoriesSearch'] = Category::whereIn('id', $searchInputs['categoriesSearch'])->pluck('name');
        }

        // Concentration indicator parameters
        if (!empty($searchInputs['concentrationIndicatorSearch'])) {
            $searchParameters['concentrationIndicatorSearch'] = ConcentrationIndicator::whereIn('id', $searchInputs['concentrationIndicatorSearch'])->pluck('name');
        }

        // Source parameters
        if (!empty($searchInputs['sourceSearch'])) {
            $searchParameters['sourceSearch'] = SuspectListExchangeSource::whereIn('id', $searchInputs['sourceSearch'])->pluck('code');
        }

        // Quality parameters
        if (!empty($searchInputs['qualityAnalyticalMethodsSearch'])) {
            $searchParameters['ratings'] = QualityEmpodatAnalyticalMethods::whereIn('id', $searchInputs['qualityAnalyticalMethodsSearch'])->get();
        }

        // Year parameters
        if (!is_null($request->input('year_from'))) {
            $searchParameters['year_from'] = $request->input('year_from');
        }

        if (!is_null($request->input('year_to'))) {
            $searchParameters['year_to'] = $request->input('year_to');
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
            Log::error('Query logging failed: ' . $e->getMessage(), [
                'query_hash' => $queryHash,
                'user_id' => Auth::id()
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
            'files',
        ])->findOrFail($id);

        return view('empodat_suspect.show', [
            'record' => $record,
        ]);
    }

    /**
     * Start CSV download job
     */
    public function startDownloadJob($query_log_id)
    {
        if (!Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');
            return back();
        }

        // Increase memory limit for large exports
        ini_set('memory_limit', '512M');

        // Disable query log to save memory
        DB::connection()->disableQueryLog();

        // Disable debugbar to prevent memory issues
        if (app()->has('debugbar')) {
            app('debugbar')->disable();
        }

        try {
            // Get the query log record
            $queryLog = QueryLog::findOrFail($query_log_id);

            // Generate filename
            $filename = 'empodat_suspect_export_uid_' . Auth::id() . '_' . now()->format('YmdHis') . '.csv';

            // Get request information for logging
            $ip = request()->ip();
            $userAgent = request()->userAgent();

            // Create an export download record for tracking
            $exportDownload = ExportDownload::create([
                'user_id' => Auth::id(),
                'filename' => $filename,
                'format' => 'csv',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'database_key' => 'empodat_suspect',
                'status' => 'processing',
                'started_at' => Carbon::now()
            ]);

            // Associate with the query log
            $exportDownload->queryLogs()->attach($query_log_id);

            // Process the export directly
            $startTime = microtime(true);
            $directory = 'exports/empodat_suspect';

            // Make sure the directory exists
            Storage::makeDirectory($directory);

            $path = Storage::path("{$directory}/{$filename}");
            $handle = fopen($path, 'w');

            if (!$handle) {
                throw new \Exception("Unable to open file for writing: {$path}");
            }

            // Write CSV headers
            $headers = [
                'ID',
                'Norman SUS ID',
                'Substance Name',
                'Concentration',
                'Units',
                'IP Max',
                'Based on HRMS Library',
                'Country Name',
                'Country Code',
                'Sampling Year',
                'Sample Code',
                'Sampling Station',
                'Station ID',
                'Export Date'
            ];
            fputcsv($handle, $headers);

            // Build the query from the query log
            $content = json_decode($queryLog->content, true);
            $requestData = $content['request'] ?? [];

            // Process search fields
            $countrySearch = is_array($requestData['countrySearch'] ?? null)
                ? $requestData['countrySearch']
                : json_decode($requestData['countrySearch'] ?? '[]', true);

            $matrixSearch = is_array($requestData['matrixSearch'] ?? null)
                ? $requestData['matrixSearch']
                : json_decode($requestData['matrixSearch'] ?? '[]', true);

            $sourceSearch = is_array($requestData['sourceSearch'] ?? null)
                ? $requestData['sourceSearch']
                : json_decode($requestData['sourceSearch'] ?? '[]', true);

            $analyticalMethodSearch = is_array($requestData['analyticalMethodSearch'] ?? null)
                ? $requestData['analyticalMethodSearch']
                : json_decode($requestData['analyticalMethodSearch'] ?? '[]', true);

            $categoriesSearch = is_array($requestData['categoriesSearch'] ?? null)
                ? $requestData['categoriesSearch']
                : json_decode($requestData['categoriesSearch'] ?? '[]', true);

            $typeDataSourcesSearch = is_array($requestData['typeDataSourcesSearch'] ?? null)
                ? $requestData['typeDataSourcesSearch']
                : json_decode($requestData['typeDataSourcesSearch'] ?? '[]', true);

            $concentrationIndicatorSearch = is_array($requestData['concentrationIndicatorSearch'] ?? null)
                ? $requestData['concentrationIndicatorSearch']
                : json_decode($requestData['concentrationIndicatorSearch'] ?? '[]', true);

            $substances = is_array($requestData['substances'] ?? null)
                ? $requestData['substances']
                : json_decode($requestData['substances'] ?? '[]', true);

            $yearFrom = $requestData['year_from'] ?? null;
            $yearTo = $requestData['year_to'] ?? null;

            // STEP 1: Query the materialized view to get filtered station IDs
            $stationFiltersQuery = DB::table('empodat_suspect_station_filters');

            // Apply filters
            if (!empty($countrySearch)) {
                $stationFiltersQuery->whereIn('country_id', $countrySearch);
            }

            if (!empty($matrixSearch)) {
                $stationFiltersQuery->whereIn('matrix_id', $matrixSearch);
            }

            if (!empty($substances)) {
                $stationFiltersQuery->whereIn('substance_id', $substances);
            }

            if (!empty($concentrationIndicatorSearch)) {
                $stationFiltersQuery->whereIn('concentration_indicator_id', $concentrationIndicatorSearch);
            }

            if (!empty($yearFrom)) {
                $stationFiltersQuery->where('sampling_date_year', '>=', $yearFrom);
            }

            if (!empty($yearTo)) {
                $stationFiltersQuery->where('sampling_date_year', '<=', $yearTo);
            }

            if (!empty($typeDataSourcesSearch)) {
                $stationFiltersQuery->whereIn('data_source_id', $typeDataSourcesSearch);
            }

            if (!empty($analyticalMethodSearch)) {
                $stationFiltersQuery->whereIn('method_id', $analyticalMethodSearch);
            }

            // Get distinct station_ids
            $filteredStationIds = $stationFiltersQuery->distinct()->pluck('station_id');

            // STEP 2: Query empodat_suspect_main with filtered station IDs
            $baseQuery = EmpodatSuspectMain::query()
                ->select('empodat_suspect_main.*')
                ->selectSub(function ($query) {
                    $query->select('sampling_date_year')
                          ->from('empodat_suspect_station_filters')
                          ->whereColumn('empodat_suspect_station_filters.station_id', 'empodat_suspect_main.station_id')
                          ->limit(1);
                }, 'sampling_year')
                ->whereIn('station_id', $filteredStationIds);

            // Apply additional filters
            if (!empty($substances)) {
                $baseQuery->whereIn('substance_id', $substances);
            }

            // Apply category filter
            if (!empty($categoriesSearch)) {
                $baseQuery->whereHas('substance.categories', function ($q) use ($categoriesSearch) {
                    $q->whereIn('susdat_categories.id', $categoriesSearch);
                });
            }

            // Apply SLE source filter
            if (!empty($sourceSearch)) {
                $baseQuery->whereHas('substance', function ($q) use ($sourceSearch) {
                    $q->whereHas('sources', function ($sourceQuery) use ($sourceSearch) {
                        $sourceQuery->whereIn('sle_sources.id', $sourceSearch);
                    });
                });
            }

            // Process records in chunks
            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            $baseQuery->with([
                'substance',
                'station.country',
            ])->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    // Get country information safely
                    $countryName = '';
                    $countryCode = '';
                    if ($record->station && $record->station->country_id) {
                        $country = $record->station->getRelation('country');
                        if ($country) {
                            $countryName = $country->name ?? '';
                            $countryCode = $country->code ?? '';
                        }
                    }

                    $row = [
                        $record->id,
                        $record->substance && $record->substance->code ? 'NS' . $record->substance->code : '',
                        $record->substance->name ?? '',
                        $record->concentration ?? '',
                        $record->units ?? '',
                        $record->ip_max ?? '',
                        $record->based_on_hrms_library ? 'TRUE' : 'FALSE',
                        $countryName,
                        $countryCode,
                        $record->sampling_year ?? '',
                        $record->station->short_sample_code ?? '',
                        $record->station->name ?? '',
                        $record->station_id ?? '',
                        $exportDate
                    ];
                    fputcsv($handle, $row);
                    $totalExported++;
                }

                // Free memory after each chunk
                unset($records);
                gc_collect_cycles();
            });

            fclose($handle);

            // Get file size and processing time
            $fileSize = Storage::size("{$directory}/{$filename}");
            $formattedFileSize = $this->formatBytes($fileSize);
            $processingTime = round(microtime(true) - $startTime, 2);

            // Update the export download record
            $exportDownload->update([
                'status' => 'completed',
                'record_count' => $totalExported,
                'file_size_bytes' => $fileSize,
                'file_size_formatted' => $formattedFileSize,
                'processing_time_seconds' => $processingTime,
                'completed_at' => Carbon::now()
            ]);

            Log::info("Empodat Suspect export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Clear any remaining memory
            gc_collect_cycles();

            // Redirect to download with success message
            session()->flash('success', "Export complete: {$totalExported} records exported in {$processingTime} seconds.");
            return redirect()->route('empodat_suspect.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error("Empodat Suspect export failed: " . $e->getMessage());

            // Update export download record if it exists
            if (isset($exportDownload)) {
                $exportDownload->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'completed_at' => Carbon::now()
                ]);
            }

            session()->flash('error', 'Export failed: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Download CSV file
     */
    public function downloadCsv($filename)
    {
        $directory = 'exports/empodat_suspect';
        $path = Storage::path("{$directory}/{$filename}");

        if (!file_exists($path)) {
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

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
