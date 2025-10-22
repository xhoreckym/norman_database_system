<?php

namespace App\Http\Controllers\EmpodatSuspect;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\EmpodatSuspect\EmpodatSuspectMain;
use App\Models\Empodat\EmpodatStation;
use App\Models\Susdat\Substance;
use App\Models\Susdat\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmpodatSuspectController extends Controller
{
    public function filter(Request $request)
    {
        // Get all stations that have empodat_suspect records
        $stations = EmpodatStation::query()
            ->join('empodat_suspect_main', 'empodat_stations.id', '=', 'empodat_suspect_main.station_id')
            ->select('empodat_stations.id', 'empodat_stations.name', 'empodat_stations.short_sample_code')
            ->distinct()
            ->orderBy('empodat_stations.short_sample_code', 'asc')
            ->get();

        $stationList = [];
        foreach ($stations as $station) {
            $stationList[$station->id] = ($station->short_sample_code ?? '') . ' - ' . ($station->name ?? '');
        }

        // Get all categories (from SUSDAT)
        $categories = Category::orderBy('name', 'asc')
            ->select('id', 'name', 'abbreviation')
            ->get()
            ->keyBy('id');

        return view('empodat_suspect.filter', [
            'request' => $request,
            'stationList' => $stationList,
            'categories' => $categories,
        ]);
    }

    public function search(Request $request)
    {
        try {
            $stationSearch = $request->input('stationSearch', []);
            $substances = $request->input('substances', []);

            // Decode JSON strings if needed (from Alpine multiselect)
            if (is_string($stationSearch)) {
                $decoded = json_decode($stationSearch, true);
                $stationSearch = is_array($decoded) ? $decoded : [];
            }

            if (is_string($substances)) {
                $decoded = json_decode($substances, true);
                $substances = is_array($decoded) ? $decoded : [];
            }

            // Ensure they are arrays
            if (!is_array($stationSearch)) {
                $stationSearch = [];
            }

            if (!is_array($substances)) {
                $substances = [];
            }

            // Logic:
            // - If only station selected: show only that station's columns
            // - If substance + station: show only that station's columns for that substance
            // - If only substance: show all stations for that substance

            // Get station mappings for column headers
            $stationMappingsQuery = DB::table('empodat_suspect_xlsx_stations_mapping')
                ->orderBy('xlsx_name');

            // If stations are filtered, only show those station columns
            if (!empty($stationSearch)) {
                $stationMappingsQuery->whereIn('station_id', $stationSearch);
            }

            $stationMappings = $stationMappingsQuery->get();

            // Build query for substances with records
            $query = DB::table('empodat_suspect_main')
                ->join('susdat_substances', 'empodat_suspect_main.substance_id', '=', 'susdat_substances.id')
                ->select(
                    'susdat_substances.id as substance_id',
                    'susdat_substances.code',
                    'susdat_substances.name',
                    'susdat_substances.smiles',
                    DB::raw('MAX(empodat_suspect_main.ip) as ip'),
                    DB::raw('MAX(empodat_suspect_main.ip_max) as ip_max'),
                    DB::raw('MAX(CASE WHEN empodat_suspect_main.based_on_hrms_library THEN 1 ELSE 0 END) as based_on_hrms_library'),
                    DB::raw('MAX(empodat_suspect_main.units) as units')
                )
                ->groupBy('susdat_substances.id', 'susdat_substances.code', 'susdat_substances.name', 'susdat_substances.smiles');

            // Apply filters
            if (!empty($stationSearch)) {
                $query->whereIn('empodat_suspect_main.station_id', $stationSearch);
            }

            if (!empty($substances)) {
                $query->whereIn('susdat_substances.id', $substances);
            }

            // Get substances
            $substancesData = $query->get();

            // Get concentration data for filtered substances
            $concentrationsQuery = DB::table('empodat_suspect_main')
                ->join('empodat_suspect_xlsx_stations_mapping', 'empodat_suspect_main.xlsx_station_mapping_id', '=', 'empodat_suspect_xlsx_stations_mapping.id')
                ->whereIn('empodat_suspect_main.substance_id', $substancesData->pluck('substance_id'))
                ->select(
                    'empodat_suspect_main.substance_id',
                    'empodat_suspect_xlsx_stations_mapping.xlsx_name',
                    'empodat_suspect_main.concentration'
                );

            // Also filter concentrations by station if stations are selected
            if (!empty($stationSearch)) {
                $concentrationsQuery->whereIn('empodat_suspect_main.station_id', $stationSearch);
            }

            $concentrations = $concentrationsQuery->get()->groupBy('substance_id');

            // Build pivoted data
            $pivotedData = [];
            foreach ($substancesData as $index => $substance) {
                $row = [
                    'num' => $index + 1,
                    'norman_id' => $substance->code ? 'NS' . $substance->code : 'N/A',
                    'name' => $substance->name ?? 'N/A',
                    'smiles' => $substance->smiles ?? 'N/A',
                    'ip' => $substance->ip ?? 'N/A',
                    'ip_max' => $substance->ip_max ?? 'N/A',
                    'based_on_hrms_library' => $substance->based_on_hrms_library ? 'TRUE' : 'FALSE',
                    'units' => $substance->units ?? 'N/A',
                    'stations' => []
                ];

                // Add concentration for each station
                foreach ($stationMappings as $mapping) {
                    $concentration = 'NA';
                    if (isset($concentrations[$substance->substance_id])) {
                        $stationData = $concentrations[$substance->substance_id]
                            ->where('xlsx_name', $mapping->xlsx_name)
                            ->first();
                        if ($stationData) {
                            $concentration = $stationData->concentration;
                        }
                    }
                    $row['stations'][$mapping->xlsx_name] = $concentration;
                }

                $pivotedData[] = $row;
            }

            // Get total count of records in database
            $totalCount = $this->getDatabaseEntityCount('empodat_suspect');

            return view('empodat_suspect.index', [
                'pivotedData' => $pivotedData,
                'stationMappings' => $stationMappings,
                'matchedCount' => count($pivotedData),
                'totalCount' => $totalCount,
                'stationSearch' => $stationSearch,
                'substances' => $substances,
                'displayOption' => $request->input('displayOption', '1'),
                'request' => $request,
            ]);

        } catch (\Exception $e) {
            Log::error('Empodat Suspect search failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('empodat_suspect.search.filter')
                ->with('error', 'Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Process search inputs from request
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
     * Build search parameters for display
     */
    private function buildSearchParameters(array $searchInputs, Request $request): array
    {
        $searchParameters = [];

        // Station parameters
        if (!empty($searchInputs['stationSearch'])) {
            $searchParameters['stationSearch'] = EmpodatStation::whereIn('id', $searchInputs['stationSearch'])
                ->pluck('name');
        }

        // Substance parameters
        if (!empty($request->input('substances'))) {
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))
                ->pluck('name');
        }

        // Category parameters
        if (!empty($searchInputs['categoriesSearch'])) {
            $searchParameters['categoriesSearch'] = Category::whereIn('id', $searchInputs['categoriesSearch'])
                ->pluck('name');
        }

        // IP_max range
        if (!empty($searchInputs['ipMaxMin']) || !empty($searchInputs['ipMaxMax'])) {
            $searchParameters['ipMaxRange'] = [
                'min' => $searchInputs['ipMaxMin'],
                'max' => $searchInputs['ipMaxMax'],
            ];
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
     * Log the query
     */
    private function logQuery($query, array $mainRequest, Request $request): ?int
    {
        if ($request->has('page')) {
            return QueryLog::orderBy('id', 'desc')->first()?->id;
        }

        $databaseKey = 'empodat_suspect';
        $suspectCount = $this->getDatabaseEntityCount($databaseKey);
        $now = now();
        $bindings = $query->getBindings();
        $sql = vsprintf(str_replace('?', "'%s'", $query->toSql()), $bindings);
        $queryHash = hash('sha256', $sql);

        $actualCount = QueryLog::where('query_hash', $queryHash)
                               ->where('total_count', $suspectCount)
                               ->value('actual_count');

        try {
            QueryLog::insert([
                'content' => json_encode(['request' => $mainRequest, 'bindings' => $bindings]),
                'query' => $sql,
                'user_id' => Auth::id(),
                'total_count' => $suspectCount,
                'actual_count' => $actualCount,
                'database_key' => $databaseKey,
                'query_hash' => $queryHash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return QueryLog::orderBy('id', 'desc')->first()->id;

        } catch (\Exception $e) {
            Log::error('Query logging failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Apply pagination
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

    public function startDownloadJob($query_log_id)
    {
        if (!Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');
            return back();
        }

        session()->flash('error', 'Download functionality not yet implemented.');
        return back();
    }

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

    public function show($id)
    {
        return view('empodat_suspect.show', [
            'id' => $id,
        ]);
    }

    public function edit($id)
    {
        if (!auth()->check() ||
            !(auth()->user()->hasRole('super_admin') ||
              auth()->user()->hasRole('admin') ||
              auth()->user()->hasRole('empodat_suspect'))) {
            session()->flash('error', 'You do not have permission to edit Empodat Suspect records.');
            return redirect()->route('empodat_suspect.search.search');
        }

        return view('empodat_suspect.edit', [
            'id' => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->check() ||
            !(auth()->user()->hasRole('super_admin') ||
              auth()->user()->hasRole('admin') ||
              auth()->user()->hasRole('empodat_suspect'))) {
            session()->flash('error', 'You do not have permission to update Empodat Suspect records.');
            return redirect()->route('empodat_suspect.search.search');
        }

        session()->flash('success', 'Empodat Suspect record updated successfully.');

        return redirect()->route('empodat_suspect.search.show', $id);
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
