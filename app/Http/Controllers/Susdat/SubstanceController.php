<?php

namespace App\Http\Controllers\Susdat;

use Exception;
use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
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
    $audits = $substance->audits()
      ->with('user')
      ->orderBy('created_at', 'desc')
      ->paginate(20);

    return view('susdat.audits', [
      'substance' => $substance,
      'audits' => $audits,
    ]);
  }

  public function withAudits()
  {
    $substances = Substance::whereHas('audits')
      ->withCount('audits')
      ->orderBy('audits_count', 'desc')
      ->paginate(50);

    return view('susdat.with-audits', [
      'substances' => $substances,
    ]);
  }

  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    $substances = Substance::select([
      'id',
      'code',
      'name',
      'cas_number',
      'smiles',
      'stdinchikey',
      'dtxid',
      'pubchem_cid',
      'chemspider_id',
      'molecular_formula',
      'mass_iso',
      'deleted_at',
    ])
    ->orderBy('code', 'asc')
    ->paginate(100);

    // Get category IDs for the paginated substances
    $substanceIds = $substances->pluck('id')->toArray();
    $categoryIds = [];
    
    if (!empty($substanceIds)) {
      $categoryIds = DB::table('susdat_category_substance')
        ->whereIn('substance_id', $substanceIds)
        ->select([
          'substance_id AS id',
        ])
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
        ->select([
          'substance_id AS id',
        ])
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
      $sourceList[$s->id] = $s->code . ' - ' . $s->name;
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
      'substance' => Substance::findOrFail($id)
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
      $sourceList[$s->id] = $s->code . ' - ' . $s->name;
    }
    return view('susdat.edit', [
      'substance' => $substance,
      'categories' => $categories,
      'sources' => $sources,
      'sourceList' => $sourceList,
      'editables' => $editables
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
      return redirect()->route('substances.edit', ['substance' => $id])->with('error', $e->getMessage());
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
      $sourceList[$s->id] = $s->code . ' - ' . $s->name;
    }

    return view('susdat.filter', [
      'request' => $request,
      'categories' => $categories,
      'sources' => $sources,
      'sourceList' => $sourceList
    ]);
  }

  /**
   * Process the search and display results
   */
  public function search(Request $request)
  {
    $substancesCount = Substance::count();

    // get all categories and sources by id
    $allCategories = Category::all()->pluck('id')->toArray();
    $allSources = SuspectListExchangeSource::all()->pluck('id')->toArray();

    if (is_array($request->input('categoriesSearch'))) {
      $categoriesSearch = $request->input('categoriesSearch');
    } else {
      $categoriesSearch = json_decode($request->input('categoriesSearch')) ?? [];
    }

    if (is_array($request->input('sourcesSearch'))) {
      $sourcesSearch = $request->input('sourcesSearch');
    } else {
      $sourcesSearch = json_decode($request->input('sourcesSearch')) ?? [];
    }

    if (is_array($request->input('substancesSearch'))) {
      $substancesSearch = $request->input('substancesSearch');
    } else {
      $substancesSearch = json_decode($request->input('substancesSearch')) ?? [];
    }

    $columns = [
      'id',
      'code',
      'name',
      'cas_number',
      'smiles',
      'stdinchikey',
      'dtxid',
      'pubchem_cid',
      'chemspider_id',
      'molecular_formula',
      'mass_iso',
      'deleted_at',
    ];

    // Build the main query with category filtering
    $substances = DB::table('susdat_substances')
      ->select($columns);
    
    // Apply search filters
    if ($request->input('searchCategory') == 1 && !empty($categoriesSearch)) {
      $substances = $substances->whereIn('id', function($query) use ($categoriesSearch) {
        $query->select('substance_id')
          ->from('susdat_category_substance')
          ->whereIn('category_id', $categoriesSearch);
      });
    } elseif ($request->input('searchSource') == 1 && !empty($sourcesSearch)) {
      $substances = $substances->whereIn('id', function($query) use ($sourcesSearch) {
        $query->select('substance_id')
          ->from('susdat_source_substance')
          ->whereIn('source_id', $sourcesSearch);
      });
    } elseif ($request->input('searchSubstance') == 1 && !empty($substancesSearch)) {
      $substances = $substances->whereIn('id', $substancesSearch);
      $categoriesSearch = $allCategories;
      $sourcesSearch = $allSources;
    }

    // Add category aggregation using a subquery to avoid duplicates
    $substances = $substances->addSelect(DB::raw("(
      SELECT STRING_AGG(category_id::text, '|' ORDER BY category_id)
      FROM susdat_category_substance
      WHERE substance_id = susdat_substances.id
    ) AS category_ids"));

    // Apply ordering
    if (!is_null($request->input('order_by_column')) && !is_null($request->input('order_by_direction'))) {
      // Map view column index to database column name
      $viewColumns = $this->getViewColumns();
      $columnIndex = $request->input('order_by_column');
      $columnName = $viewColumns[$columnIndex] ?? 'NORMAN SusDat ID';
      
      // Map display column names to database column names
      $columnMapping = [
        '' => 'id', // Empty header maps to id for icons column
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
      
      $dbColumn = $columnMapping[$columnName] ?? 'code';
      $direction = $this->orderByList((int)$request->input('order_by_direction')) ?? 'asc';
      $substances = $substances->orderBy('susdat_substances.' . $dbColumn, $direction);
    } else {
      $substances = $substances->orderBy('susdat_substances.code', 'asc');
    }

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

    if(!$request->has('page')){
      $now = now();
      $bindings = $substances->getBindings();
      $sql = vsprintf(str_replace('?', "'%s'", $substances->toSql()), $bindings);
      // try to find same SQL query in the QueryLog table with same total_count based on the query_hash
      $actual_count = QueryLog::where('query_hash', hash('sha256', $sql))->where('total_count', $substancesCount)->value('actual_count');
      
      try {
        QueryLog::insert([
          'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
          'query' => $sql,
          'user_id' => Auth::check() ? Auth::id() : null,
          'total_count' => $substancesCount,
          'actual_count' => is_null($actual_count) ? null : $actual_count,
          'database_key' => $database_key,
          'query_hash' => hash('sha256', $sql),
          'created_at' => $now,
          'updated_at' => $now,
        ]);
      } catch (\Exception $e) {
        session()->flash('error', 'An error occurred while processing your request.');
      }
    }

    // Get the IDs before pagination for the source query
    $substanceIds = $substances->pluck('id')->toArray();
    
    $substances = $substances->paginate(30);

    // Get source IDs for the paginated substances
    $sourceIds = [];
    if (!empty($substanceIds)) {
      $sourceIds = DB::table('susdat_substances')
        ->whereIn('id', $substanceIds)
        ->select([
          'id',
        ])
        ->selectRaw("(
          SELECT STRING_AGG(source_id::text, '|' ORDER BY source_id)
          FROM susdat_source_substance
          WHERE substance_id = susdat_substances.id
        ) AS source_ids")
        ->get()
        ->keyBy('id')
        ->toArray();
    }

    // Prepare filter parameters
    $filter['order_by_direction'] = (int)$request->input('order_by_direction') ?? 0;
    $filter['order_by_column'] = $request->input('order_by_column') ?? 1;

    // prepare list for multiple selects
    $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
    $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
    $sourcesList = [];
    $categoriesList = [];
    $sourceList = [];
    foreach ($sources as $s) {
      $sourceList[$s->id] = $s->code . ' - ' . $s->name;
    }

    foreach ($categories as $s) {
      $categoriesList[$s->id] = $s->name;
    }

    // Build search parameters for display
    $searchParameters = [];
    if (!empty($categoriesSearch)) {
      $searchParameters['Categories'] = collect($categoriesSearch)->map(function($id) use ($categories) {
        return $categories[$id]->name ?? 'Unknown Category';
      })->toArray();
    }
    if (!empty($sourcesSearch)) {
      $searchParameters['Sources'] = collect($sourcesSearch)->map(function($id) use ($sources) {
        return $sources[$id]->code . ' - ' . $sources[$id]->name ?? 'Unknown Source';
      })->toArray();
    }
    if (!empty($substancesSearch)) {
      $searchParameters['Substances'] = $substancesSearch;
    }

    return view('susdat.index', [
      'columns' => $this->getViewColumns(),
      'substances' => $substances,
      'substancesCount' => $substancesCount,
      'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
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

  protected function getEditableColumns()
  {
    return [
      'code',
      'name',
      'name_dashboard',
      'name_chemspider',
      'name_iupac',
      'cas_number',
      'smiles',
      'smiles_dashboard',
      'stdinchi',
      'stdinchikey',
      'pubchem_cid',
      'chemspider_id',
      'dtxid',
      'molecular_formula',
      'mass_iso',
      'metadata_synonyms',
      'metadata_cas',
      'metadata_ms_ready',
      'metadata_general',
    ];
  }

  private function getSelectColumns()
  {
    return [
      'id',
      'code',
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