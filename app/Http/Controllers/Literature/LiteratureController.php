<?php

namespace App\Http\Controllers\Literature;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\Project;
use App\Models\Literature\LiteratureTempMain;
use App\Models\Literature\Species;
use App\Models\Literature\TypeOfNumericQuantity;
use App\Models\List\Country;
use App\Models\List\Tissue;
use App\Models\List\Matrix;
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
            ->select('list_species.id', 'list_species.name_latin', 'list_species.name', 'list_species.class')
            ->distinct()
            ->orderBy('list_species.name', 'asc')
            ->get();

        $speciesList = [];
        $speciesWithClass = [];
        foreach ($species as $s) {
            // Build label: Name (Latin) [Class]
            $label = $s->name;
            if ($s->name_latin) {
                $label .= ' (' . $s->name_latin . ')';
            }
            if ($s->class) {
                $label .= ' [' . $s->class . ']';
            }

            $speciesList[$s->id] = $label;
            $speciesWithClass[] = [
                'id' => $s->id,
                'label' => $label,
                'class' => $s->class,
            ];
        }

        // Get all type of numeric quantities that have literature records
        $typeOfNumericQuantities = TypeOfNumericQuantity::query()
            ->join('literature_temp_main', 'list_type_of_numeric_quantities.id', '=', 'literature_temp_main.type_of_numeric_quantity_id')
            ->select('list_type_of_numeric_quantities.id', 'list_type_of_numeric_quantities.name')
            ->distinct()
            ->orderBy('list_type_of_numeric_quantities.name', 'asc')
            ->get();

        $typeOfNumericQuantityList = [];
        foreach ($typeOfNumericQuantities as $t) {
            $typeOfNumericQuantityList[$t->id] = $t->name;
        }

        // Get all unique classes from species that have literature records
        $classes = Species::query()
            ->join('literature_temp_main', 'list_species.id', '=', 'literature_temp_main.species_id')
            ->select('list_species.class')
            ->whereNotNull('list_species.class')
            ->distinct()
            ->orderBy('list_species.class', 'asc')
            ->pluck('list_species.class');

        $classList = [];
        foreach ($classes as $class) {
            $classList[$class] = $class;
        }

        // Get all tissues that have literature records
        $tissues = Tissue::query()
            ->join('literature_temp_main', 'list_tissues.id', '=', 'literature_temp_main.tissue_id')
            ->select('list_tissues.id', 'list_tissues.name')
            ->distinct()
            ->orderBy('list_tissues.name', 'asc')
            ->get();

        $tissueList = [];
        foreach ($tissues as $t) {
            $tissueList[$t->id] = $t->name;
        }

        // Get all matrices that have literature records
        $matrices = Matrix::query()
            ->join('literature_temp_main', 'list_matrices.id', '=', 'literature_temp_main.matrix_id')
            ->select('list_matrices.id', 'list_matrices.name')
            ->distinct()
            ->orderBy('list_matrices.name', 'asc')
            ->get();

        $matrixList = [];
        foreach ($matrices as $m) {
            $matrixList[$m->id] = $m->name;
        }

        // Get all projects that have files associated with literature records
        $projects = Project::query()
            ->join('files', 'projects.id', '=', 'files.project_id')
            ->join('file_literature_temp_main', 'files.id', '=', 'file_literature_temp_main.file_id')
            ->select('projects.id', 'projects.name', 'projects.abbreviation')
            ->distinct()
            ->orderBy('projects.name', 'asc')
            ->get();

        $projectList = [];
        foreach ($projects as $project) {
            $projectList[$project->id] = $project->name;
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
            'speciesWithClass' => $speciesWithClass,
            'typeOfNumericQuantityList' => $typeOfNumericQuantityList,
            'classList' => $classList,
            'tissueList' => $tissueList,
            'matrixList' => $matrixList,
            'projectList' => $projectList,
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
                'typeOfNumericQuantitySearch' => [],
                'classSearch' => [],
                'tissueSearch' => [],
                'matrixSearch' => [],
                'categoriesSearch' => [],
                'fileSearch' => [],
                'projectSearch' => [],
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
                ->byTypeOfNumericQuantity($searchInputs['typeOfNumericQuantitySearch'])
                ->byClasses($searchInputs['classSearch'])
                ->byTissues($searchInputs['tissueSearch'])
                ->byMatrices($searchInputs['matrixSearch'])
                ->byCategories($searchInputs['categoriesSearch'])
                ->byFiles($searchInputs['fileSearch'])
                ->byProjects($searchInputs['projectSearch']);

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
            $searchParameters['speciesSearch'] = Species::whereIn('id', $searchInputs['speciesSearch'])->pluck('name');
        }

        // Type of numeric quantity parameters
        if (!empty($searchInputs['typeOfNumericQuantitySearch'])) {
            $searchParameters['typeOfNumericQuantitySearch'] = TypeOfNumericQuantity::whereIn('id', $searchInputs['typeOfNumericQuantitySearch'])->pluck('name');
        }

        // Class parameters
        if (!empty($searchInputs['classSearch'])) {
            $searchParameters['classSearch'] = collect($searchInputs['classSearch']);
        }

        // Tissue parameters
        if (!empty($searchInputs['tissueSearch'])) {
            $searchParameters['tissueSearch'] = Tissue::whereIn('id', $searchInputs['tissueSearch'])->pluck('name');
        }

        // Matrix parameters
        if (!empty($searchInputs['matrixSearch'])) {
            $searchParameters['matrixSearch'] = Matrix::whereIn('id', $searchInputs['matrixSearch'])->pluck('name');
        }

        // Substance parameters
        if (!empty($request->input('substances'))) {
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
        }

        // Category parameters
        if (!empty($searchInputs['categoriesSearch'])) {
            $searchParameters['categoriesSearch'] = Category::whereIn('id', $searchInputs['categoriesSearch'])->pluck('name');
        }

        // File parameters
        if (!empty($searchInputs['fileSearch'])) {
            $searchParameters['fileSearch'] = \App\Models\Backend\File::whereIn('id', $searchInputs['fileSearch'])->pluck('name');
        }

        // Project parameters
        if (!empty($searchInputs['projectSearch'])) {
            $searchParameters['projectSearch'] = Project::whereIn('id', $searchInputs['projectSearch'])->pluck('name');
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
        $record = LiteratureTempMain::with([
            'country',
            'species',
            'substance',
            'tissue',
            'sex',
            'lifeStage',
            'habitatType',
            'commonName',
            'useCategory',
            'concentrationUnit',
            'typeOfNumericQuantity',
        ])->findOrFail($id);

        return view('literature.show', [
            'record' => $record,
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
