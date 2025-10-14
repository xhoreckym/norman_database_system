<?php

namespace App\Http\Controllers\Literature;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Literature\LiteratureTempMain;
use App\Models\Literature\Species;
use App\Models\List\Country;
use App\Models\List\HabitatType;
use App\Models\Susdat\Substance;
use App\Models\Susdat\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LiteratureController extends Controller
{
    public function filter(Request $request)
    {
        // Get all countries that have literature records
        $countries = Country::query()
            ->join('literature_temp_main', 'list_countries.id', '=', 'literature_temp_main.country_id')
            ->select('list_countries.id', 'list_countries.name', 'list_countries.code')
            ->distinct()
            ->orderBy('list_countries.name', 'asc')
            ->get();

        $countryList = [];
        foreach ($countries as $country) {
            $countryList[$country->id] = $country->name . ' - ' . $country->code;
        }

        // Get all species that have literature records
        $species = Species::query()
            ->join('literature_temp_main', 'list_species.id', '=', 'literature_temp_main.species_id')
            ->select('list_species.id', 'list_species.name_latin', 'list_species.name')
            ->distinct()
            ->orderBy('list_species.name_latin', 'asc')
            ->get();

        $speciesList = [];
        foreach ($species as $s) {
            $speciesList[$s->id] = $s->name_latin . ($s->name ? ' (' . $s->name . ')' : '');
        }

        // Get all habitat types that have literature records
        $habitatTypes = HabitatType::query()
            ->join('literature_temp_main', 'list_habitat_types.id', '=', 'literature_temp_main.habitat_type_id')
            ->select('list_habitat_types.id', 'list_habitat_types.name')
            ->distinct()
            ->orderBy('list_habitat_types.name', 'asc')
            ->get();

        $habitatTypeList = [];
        foreach ($habitatTypes as $h) {
            $habitatTypeList[$h->id] = $h->name;
        }

        // Get all categories (from SUSDAT)
        $categories = Category::orderBy('name', 'asc')
            ->select('id', 'name', 'abbreviation')
            ->get()
            ->keyBy('id');

        return view('literature.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'speciesList' => $speciesList,
            'habitatTypeList' => $habitatTypeList,
            'categories' => $categories,
        ]);
    }

    public function search(Request $request)
    {
        try {
            // Define search fields with their default values
            $searchFields = [
                'countrySearch' => [],
                'speciesSearch' => [],
                'habitatTypeSearch' => [],
                'categoriesSearch' => [],
            ];

            // Process all search inputs
            $searchInputs = $this->processSearchInput($request, $searchFields);

            // Build query
            $literatureRecords = LiteratureTempMain::query();

            // Apply filters using the scopes
            $literatureRecords = $literatureRecords
                ->byCountries($searchInputs['countrySearch'])
                ->bySubstances($request->input('substances', []))
                ->bySpecies($searchInputs['speciesSearch'])
                ->byHabitatTypes($searchInputs['habitatTypeSearch'])
                ->byCategories($searchInputs['categoriesSearch']);

            // Build search parameters for display
            $searchParameters = $this->buildSearchParameters($searchInputs, $request);

            // Prepare request data for logging
            $mainRequest = $this->prepareRequestData($request, $searchInputs);

            // Log query if not paginated request
            $queryLogId = $this->logQuery($literatureRecords, $mainRequest, $request);

            // Get the total count before pagination (for "Fast data preview" mode)
            $literatureMatchedCount = $literatureRecords->count();

            // Apply pagination
            $literatureRecords = $this->applyPagination($literatureRecords, $request);

            // Eager load all necessary relationships after pagination to avoid N+1 problems
            $literatureRecords->load([
                'country',
                'species',
                'substance',
                'tissue',
                'sex',
                'lifeStage',
                'habitatType',
                'commonName',
            ]);

            // Get total count
            $database_key = 'literature';
            $literatureObjectsCount = $this->getDatabaseEntityCount($database_key);

            return view('literature.index', [
                'literatureRecords' => $literatureRecords,
                'literatureObjectsCount' => $literatureObjectsCount,
                'literatureMatchedCount' => $literatureMatchedCount,
                'query_log_id' => $queryLogId,
                'searchParameters' => $searchParameters,
                'request' => $request,
            ] + $mainRequest);

        } catch (\Exception $e) {
            Log::error('Literature search failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return to filter page with error message
            return redirect()->route('literature.search.filter')
                ->with('error', 'Search failed due to a database error. Please try again or contact support if the problem persists.');
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
                // For simple string values, use the value directly
                // Only try JSON decoding for fields that might contain JSON arrays
                if (str_ends_with($field, 'Search') || str_ends_with($field, '[]')) {
                    $decoded = json_decode($value, true);
                    $processed[$field] = $decoded ?? $defaultValue;
                } else {
                    // Use the string value as-is for simple fields
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

        // Species parameters
        if (!empty($searchInputs['speciesSearch'])) {
            $searchParameters['speciesSearch'] = Species::whereIn('id', $searchInputs['speciesSearch'])->pluck('name_latin');
        }

        // Habitat type parameters
        if (!empty($searchInputs['habitatTypeSearch'])) {
            $searchParameters['habitatTypeSearch'] = HabitatType::whereIn('id', $searchInputs['habitatTypeSearch'])->pluck('name');
        }

        // Substance parameters
        if (!empty($request->input('substances'))) {
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
        }

        // Category parameters
        if (!empty($searchInputs['categoriesSearch'])) {
            $searchParameters['categoriesSearch'] = Category::whereIn('id', $searchInputs['categoriesSearch'])->pluck('name');
        }

        return $searchParameters;
    }

    /**
     * Prepare request data for logging
     */
    private function prepareRequestData(Request $request, array $searchInputs): array
    {
        $requestData = array_merge($searchInputs, [
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

        $databaseKey = 'literature';
        $literatureCount = $this->getDatabaseEntityCount($databaseKey);
        $now = now();
        $bindings = $query->getBindings();
        $sql = vsprintf(str_replace('?', "'%s'", $query->toSql()), $bindings);
        $queryHash = hash('sha256', $sql);

        // Check for existing query with same hash
        $actualCount = QueryLog::where('query_hash', $queryHash)
                               ->where('total_count', $literatureCount)
                               ->value('actual_count');

        try {
            QueryLog::insert([
                'content' => json_encode(['request' => $mainRequest, 'bindings' => $bindings]),
                'query' => $sql,
                'user_id' => Auth::id(),
                'total_count' => $literatureCount,
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
        $orderBy = $query->orderBy('literature_temp_main.id', 'asc');

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

    public function startDownloadJob($query_log_id)
    {
        if (!Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');
            return back();
        }

        // TODO: Implement download logic once database table is created
        
        session()->flash('error', 'Download functionality not yet implemented.');
        return back();
    }

    public function downloadCsv($filename)
    {
        $directory = 'exports/literature';
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

    public function show($id)
    {
        // TODO: Implement show logic once database table is created
        
        return view('literature.show', [
            'id' => $id,
        ]);
    }

    public function edit($id)
    {
        if (!auth()->check() || 
            !(auth()->user()->hasRole('super_admin') || 
              auth()->user()->hasRole('admin') || 
              auth()->user()->hasRole('literature'))) {
            session()->flash('error', 'You do not have permission to edit Literature records.');
            return redirect()->route('literature.search.search');
        }

        // TODO: Implement edit logic once database table is created
        
        return view('literature.edit', [
            'id' => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->check() || 
            !(auth()->user()->hasRole('super_admin') || 
              auth()->user()->hasRole('admin') || 
              auth()->user()->hasRole('literature'))) {
            session()->flash('error', 'You do not have permission to update Literature records.');
            return redirect()->route('literature.search.search');
        }

        // TODO: Implement update logic once database table is created
        
        session()->flash('success', 'Literature record updated successfully.');
        
        return redirect()->route('literature.search.show', $id);
    }

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
