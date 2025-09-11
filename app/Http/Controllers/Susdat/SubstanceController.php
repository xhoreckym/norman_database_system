<?php

namespace App\Http\Controllers\Susdat;

use Exception;
use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Models\SLE\SuspectListExchange;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SubstanceController extends Controller
{
    public function audits(Substance $substance)
    {
        $audits = $substance->audits()->with('user')->orderBy('created_at', 'desc')->paginate(20);

        return view('susdat.audits', [
            'substance' => $substance,
            'audits' => $audits,
        ]);
    }

    public function withAudits()
    {
        $substances = Substance::whereHas('audits')->withCount('audits')->orderBy('audits_count', 'desc')->paginate(50);

        return view('susdat.with-audits', [
            'substances' => $substances,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $substances = Substance::select(['id', 'code', 'name', 'cas_number', 'smiles', 'stdinchikey', 'dtxid', 'pubchem_cid', 'chemspider_id', 'molecular_formula', 'mass_iso', 'deleted_at'])
            ->orderBy('code', 'asc')
            ->paginate(100);

        // Get category IDs for the paginated substances
        $substanceIds = $substances->pluck('id')->toArray();
        $categoryIds = [];

        if (!empty($substanceIds)) {
            $categoryIds = DB::table('susdat_category_substance')
                ->whereIn('substance_id', $substanceIds)
                ->select(['substance_id AS id'])
                ->selectRaw("STRING_AGG(category_id::text, '|' ORDER BY category_id) AS category_ids")
                ->groupBy('substance_id')
                ->get()
                ->keyBy('id')
                ->toArray();
        }

        // Get source IDs for the paginated substances
        $sourceIds = [];

        if (!empty($substanceIds)) {
            $sourceIds = DB::table('susdat_source_substance')
                ->whereIn('substance_id', $substanceIds)
                ->select(['substance_id AS id'])
                ->selectRaw("STRING_AGG(source_id::text, '|' ORDER BY source_id) AS source_ids")
                ->groupBy('substance_id')
                ->get()
                ->keyBy('id')
                ->toArray();
        }

        // Add category_ids to substances
        foreach ($substances as $substance) {
            $substance->category_ids = $categoryIds[$substance->id]->category_ids ?? null;
        }

        // Get categories and sources for display
        $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
        $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code . ' - ' . $s->sanitized_name;
        }

        return view('susdat.index', [
            'columns' => $this->getViewColumns(),
            'substances' => $substances,
            'substancesCount' => Substance::count(),
            'request' => new Request(),
            'sourceIds' => $sourceIds,
            'activeCategoryids' => [],
            'activeSourceids' => [],
            'sources' => $sources,
            'sourceList' => $sourceList,
            'categories' => $categories,
            'categoriesList' => $categories->pluck('name', 'id')->toArray(),
            'orderByDirection' => $this->orderByList(),
            'filter' => [
                'order_by_direction' => 0,
                'order_by_column' => 1,
            ],
            'searchParameters' => [],
        ]);
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
    public function show($id)
    {
        return view('susdat.show', [
            'substance' => Substance::findOrFail($id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $substance = Substance::findOrFail($id);
        $editables = $this->getEditableColumns();

        $categories = Category::orderBy('name', 'asc')->get();
        $sources = SuspectListExchangeSource::orderBy('id', 'asc')->get();
        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code . ' - ' . $s->sanitized_name;
        }
        return view('susdat.edit', [
            'substance' => $substance,
            'categories' => $categories,
            'sources' => $sources,
            'sourceList' => $sourceList,
            'editables' => $editables,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $substance = Substance::findOrFail($id);
        $editables = $this->getEditableColumns();

        foreach ($editables as $key) {
            if ($request->has($key)) {
                if (substr($key, 0, 8) == 'metadata') {
                    $substance->$key = json_encode($request->input($key));
                } else {
                    $substance->$key = $request->input($key);
                }
            }
        }

        try {
            $s = $substance->save();
            session()->flash('success', 'Substance updated successfully');
            return redirect()->route('substances.show', ['substance' => $id]);
        } catch (Exception $e) {
            session()->flash('failure', 'An error occurred while updating the substance. Please contact the administrator.' . $e->getMessage());
            return redirect()
                ->route('substances.edit', ['substance' => $id])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Show the filter form
     */
    public function filter(Request $request)
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $sources = SuspectListExchangeSource::orderBy('id', 'asc')->get();
        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code . ' - ' . $s->sanitized_name;
        }

        return view('susdat.filter', [
            'request' => $request,
            'categories' => $categories,
            'sources' => $sources,
            'sourceList' => $sourceList,
        ]);
    }

    /**
     * Process the search and display results - OPTIMIZED VERSION
     */
    public function search(Request $request)
    {
        // Cache the total count for the request lifecycle
        $substancesCount = Cache::remember('substances_total_count', 300, function () {
            return Substance::count();
        });

        // Parse input parameters
        $categoriesSearch = is_array($request->input('categoriesSearch')) ? $request->input('categoriesSearch') : json_decode($request->input('categoriesSearch')) ?? [];

        $sourcesSearch = is_array($request->input('sourcesSearch')) ? $request->input('sourcesSearch') : json_decode($request->input('sourcesSearch')) ?? [];

        $substancesSearch = is_array($request->input('substancesSearch')) ? $request->input('substancesSearch') : json_decode($request->input('substancesSearch')) ?? [];

        // Get all categories and sources (cached)
        $allCategories = Cache::remember('all_category_ids', 300, function () {
            return Category::pluck('id')->toArray();
        });

        $allSources = Cache::remember('all_source_ids', 300, function () {
            return SuspectListExchangeSource::pluck('id')->toArray();
        });

        // Build the optimized query using Eloquent ORM
        $substances = Substance::query()->select(['id', 'code', 'name', 'cas_number', 'smiles', 'stdinchikey', 'dtxid', 'pubchem_cid', 'chemspider_id', 'molecular_formula', 'mass_iso', 'deleted_at']);

        // Apply search filters efficiently
        if ($request->input('searchCategory') == 1 && !empty($categoriesSearch)) {
            $substances->whereHas('categories', function ($query) use ($categoriesSearch) {
                $query->whereIn('susdat_categories.id', $categoriesSearch);
            });
        } elseif ($request->input('searchSource') == 1 && !empty($sourcesSearch)) {
            $substances->whereHas('sources', function ($query) use ($sourcesSearch) {
              $query->whereIn('source_id', $sourcesSearch);
            });
        } elseif ($request->input('searchSubstance') == 1 && !empty($substancesSearch)) {
            $substances->whereIn('id', $substancesSearch);
            $categoriesSearch = $allCategories;
            $sourcesSearch = $allSources;
        }

        // Apply ordering
        $orderColumn = 'code';
        $orderDirection = 'asc';

        if (!is_null($request->input('order_by_column')) && !is_null($request->input('order_by_direction'))) {
            $viewColumns = $this->getViewColumns();
            $columnIndex = $request->input('order_by_column');
            $columnName = $viewColumns[$columnIndex] ?? 'NORMAN SusDat ID';

            $columnMapping = [
                '' => 'id',
                'NORMAN SusDat ID' => 'code',
                'name' => 'name',
                'cas_number' => 'cas_number',
                'smiles' => 'smiles',
                'stdinchikey' => 'stdinchikey',
                'dtxid' => 'dtxid',
                'pubchem_cid' => 'pubchem_cid',
                'chemspider_id' => 'chemspider_id',
                'molecular_formula' => 'molecular_formula',
                'mass_iso' => 'mass_iso',
            ];

            $orderColumn = $columnMapping[$columnName] ?? 'code';
            $orderDirection = $this->orderByList((int) $request->input('order_by_direction')) ?? 'asc';
        }

        $substances->orderBy($orderColumn, $orderDirection);

        // Log query if not paginating
        if (!$request->has('page')) {
            $this->logQuery($substances, $request, $substancesCount, $categoriesSearch, $sourcesSearch, $substancesSearch);
        }

        // Paginate FIRST, then load relationships
        $substances = $substances->paginate(30);

        // Get substance IDs from paginated results only
        $substanceIds = $substances->pluck('id')->toArray();

        // Batch load category and source associations for paginated items only
        $categoryAssociations = [];
        $sourceAssociations = [];

        if (!empty($substanceIds)) {
            // Load category associations efficiently
            $categoryAssociations = DB::table('susdat_category_substance')
                ->whereIn('substance_id', $substanceIds)
                ->select('substance_id', 'category_id')
                ->get()
                ->groupBy('substance_id')
                ->map(function ($items) {
                    return $items->pluck('category_id')->sort()->implode('|');
                })
                ->toArray();

            // Load source associations efficiently
            $sourceAssociations = DB::table('susdat_source_substance')
                ->whereIn('substance_id', $substanceIds)
                ->select('substance_id', 'source_id')
                ->get()
                ->groupBy('substance_id')
                ->map(function ($items) {
                    return $items->pluck('source_id')->sort()->implode('|');
                })
                ->toArray();
        }

        // Attach the associations to substances
        foreach ($substances as $substance) {
            $substance->category_ids = $categoryAssociations[$substance->id] ?? null;
            $substance->source_ids = $sourceAssociations[$substance->id] ?? null;
        }

        // Transform sourceAssociations for view compatibility
        $sourceIds = [];
        foreach ($sourceAssociations as $substanceId => $sourceIdString) {
            $sourceIds[$substanceId] = (object) ['source_ids' => $sourceIdString];
        }

        // Load categories and sources for display (cached)
        $categories = Cache::remember('categories_display', 300, function () {
            return Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
        });

        $sources = Cache::remember('sources_display', 300, function () {
            return SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
        });

        $sourceList = [];
        foreach ($sources as $s) {
            $sourceList[$s->id] = $s->code . ' - ' . $s->sanitized_name;
        }

        $categoriesList = $categories->pluck('name', 'id')->toArray();

        // Build search parameters for display
        $searchParameters = $this->buildSearchParameters($categoriesSearch, $sourcesSearch, $substancesSearch, $categories, $sources);

        // Prepare filter parameters
        $filter = [
            'order_by_direction' => (int) ($request->input('order_by_direction') ?? 0),
            'order_by_column' => $request->input('order_by_column') ?? 1,
        ];

        return view('susdat.index', [
            'columns' => $this->getViewColumns(),
            'substances' => $substances,
            'substancesCount' => $substancesCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->value('id'), // More efficient than first()->id
            'request' => $request,
            'sourceIds' => $sourceIds,
            'activeCategoryids' => $categoriesSearch,
            'activeSourceids' => $sourcesSearch,
            'sources' => $sources,
            'sourceList' => $sourceList,
            'categories' => $categories,
            'categoriesList' => $categoriesList,
            'orderByDirection' => $this->orderByList(),
            'filter' => $filter,
            'searchParameters' => $searchParameters,
        ]);
    }

    /**
     * Log query for monitoring
     */
    private function logQuery($substances, $request, $substancesCount, $categoriesSearch, $sourcesSearch, $substancesSearch)
    {
        $database_key = 'susdat';
        $main_request = [
            'categoriesSearch' => $categoriesSearch,
            'sourcesSearch' => $sourcesSearch,
            'substancesSearch' => $substancesSearch,
            'searchCategory' => $request->input('searchCategory'),
            'searchSource' => $request->input('searchSource'),
            'searchSubstance' => $request->input('searchSubstance'),
            'order_by_column' => $request->input('order_by_column'),
            'order_by_direction' => $request->input('order_by_direction'),
        ];

        $now = now();
        $sql = $substances->toSql();
        $bindings = $substances->getBindings();
        $fullSql = vsprintf(str_replace('?', "'%s'", $sql), $bindings);
        $queryHash = hash('sha256', $fullSql);

        // Check for existing count
        $actual_count = QueryLog::where('query_hash', $queryHash)->where('total_count', $substancesCount)->value('actual_count');

        try {
            QueryLog::insert([
                'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
                'query' => $fullSql,
                'user_id' => Auth::check() ? Auth::id() : null,
                'total_count' => $substancesCount,
                'actual_count' => $actual_count,
                'database_key' => $database_key,
                'query_hash' => $queryHash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while processing your request.');
        }
    }

    /**
     * Build search parameters for display
     */
    private function buildSearchParameters($categoriesSearch, $sourcesSearch, $substancesSearch, $categories, $sources)
    {
        $searchParameters = [];

        if (!empty($categoriesSearch)) {
            $searchParameters['Categories'] = collect($categoriesSearch)
                ->map(function ($id) use ($categories) {
                    return $categories[$id]->name ?? 'Unknown Category';
                })
                ->toArray();
        }

        if (!empty($sourcesSearch)) {
            $searchParameters['Sources'] = collect($sourcesSearch)
                ->map(function ($id) use ($sources) {
                    return ($sources[$id]->code ?? '') . ' - ' . ($sources[$id]->name ?? 'Unknown Source');
                })
                ->toArray();
        }

        if (!empty($substancesSearch)) {
            // Fetch substance names for display instead of showing IDs
            $substanceNames = Substance::whereIn('id', $substancesSearch)
                ->select('id', 'code', 'name')
                ->get()
                ->map(function ($substance) {
                    return $substance->prefixed_code . ' - ' . ($substance->name ?? 'Unknown Substance');
                })
                ->toArray();
            
            $searchParameters['Substances'] = $substanceNames;
        }

        return $searchParameters;
    }

    protected function getEditableColumns()
    {
        return ['code', 'name', 'name_dashboard', 'name_chemspider', 'name_iupac', 'cas_number', 'smiles', 'smiles_dashboard', 'stdinchi', 'stdinchikey', 'pubchem_cid', 'chemspider_id', 'dtxid', 'molecular_formula', 'mass_iso', 'metadata_synonyms', 'metadata_cas', 'metadata_ms_ready', 'metadata_general'];
    }

    private function getSelectColumns()
    {
        return ['id', 'code', 'name', 'cas_number', 'smiles', 'stdinchikey', 'dtxid', 'pubchem_cid', 'chemspider_id', 'molecular_formula', 'mass_iso'];
    }

    private function getViewColumns()
    {
        return [
            '', // Empty header for icons column
            'NORMAN SusDat ID',
            'name',
            'cas_number',
            'smiles',
            'stdinchikey',
            'dtxid',
            'pubchem_cid',
            'chemspider_id',
            'molecular_formula',
            'mass_iso',
        ];
    }
}
