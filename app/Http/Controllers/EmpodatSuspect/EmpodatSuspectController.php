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
use Illuminate\Support\Facades\Auth;
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
}
