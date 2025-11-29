<?php

namespace App\Http\Controllers\Empodat;

use App\Http\Controllers\Controller;
use App\Models\Empodat\EmpodatMain;
use App\Models\List\Matrix;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Get the base query for statistics based on scope
     *
     * @param  string  $scope  'public' (unprotected only) or 'all' (all except deleted)
     * @return \Illuminate\Database\Query\Builder
     */
    private function getStatisticsBaseQuery(string $scope = 'public')
    {
        $query = DB::table('empodat_main as em')
            ->join('files', 'em.file_id', '=', 'files.id');

        if ($scope === 'public') {
            // Public statistics: only unprotected and non-deleted files
            $query->where('files.is_protected', false)
                ->where(function ($q) {
                    $q->where('files.is_deleted', false)
                        ->orWhereNull('files.is_deleted');
                });
        } else {
            // Admin statistics: all data except deleted files
            $query->where(function ($q) {
                $q->where('files.is_deleted', false)
                    ->orWhereNull('files.is_deleted');
            });
        }

        return $query;
    }

    /**
     * Get statistics key with scope suffix
     *
     * @param  string  $baseKey  Base key like 'country_year', 'matrix', etc.
     * @param  string  $scope  'public' or 'all'
     */
    private function getStatisticsKey(string $baseKey, string $scope = 'public'): string
    {
        return $scope === 'public' ? $baseKey : $baseKey.'_all';
    }

    /**
     * Display statistics overview page
     */
    public function index(Request $request)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();
        $allStats = [];

        // Determine which scope to display
        $scope = $request->input('scope', 'public');

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $totalRecords = 0;

        if ($empodatEntity) {
            // Get the base keys we're interested in
            $baseKeys = ['country_year', 'matrix', 'substance', 'quality'];

            foreach ($baseKeys as $baseKey) {
                $key = $this->getStatisticsKey($baseKey, $scope);

                $latestStat = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    // Store under the base key for template compatibility
                    $allStats[$baseKey] = $latestStat->meta_data;

                    // Use stored total_records from any available statistic
                    if ($totalRecords === 0 && isset($latestStat->meta_data['total_records'])) {
                        $totalRecords = $latestStat->meta_data['total_records'];
                    }
                }
            }
        }

        return view('empodat.statistics.index', [
            'empodatEntity' => $empodatEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Display country year statistics
     */
    /**
     * Generate and store country statistics data
     *
     * @param  string  $scope  'public' (unprotected only) or 'all' (all except deleted)
     */
    public function generateCountryStats(Request $request)
    {
        $scope = $request->input('scope', 'public');

        // Only admins can generate 'all' scope
        if ($scope === 'all' && ! Auth::user()->hasRole('super_admin')) {
            return back()->with('error', 'You do not have permission to generate admin statistics.');
        }

        // Extend execution time and disable memory-consuming features
        set_time_limit(600);
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        // Get the full range of years from the database (filtered by scope)
        $yearRange = $this->getStatisticsBaseQuery($scope)
            ->selectRaw('MIN(em.sampling_date_year) as min_year, MAX(em.sampling_date_year) as max_year')
            ->whereNotNull('em.sampling_date_year')
            ->first();

        $dbMinYear = $yearRange->min_year ?? date('Y') - 10;
        $dbMaxYear = $yearRange->max_year ?? date('Y');

        // Get countries with their statistics for all years (filtered by scope)
        // Using em.country_id directly instead of joining through empodat_stations
        $statistics = $this->getStatisticsBaseQuery($scope)
            ->join('list_countries as lc', 'em.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                'em.sampling_date_year',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('em.sampling_date_year')
            ->groupBy('lc.name', 'lc.code', 'em.sampling_date_year')
            ->orderBy('lc.name')
            ->orderBy('em.sampling_date_year')
            ->get();

        // Transform data into a structure suitable for storage
        $countryStats = [];
        foreach ($statistics as $stat) {
            $countryStats[$stat->country_name][$stat->sampling_date_year] = $stat->record_count;
        }

        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Calculate total records from country stats
        $totalRecords = 0;
        foreach ($countryStats as $yearData) {
            $totalRecords += array_sum($yearData);
        }

        // Create new statistics record
        $statisticsData = [
            'data' => $countryStats,
            'year_range' => [
                'min_year' => $dbMinYear,
                'max_year' => $dbMaxYear,
            ],
            'generated_at' => now()->toISOString(),
            'total_countries' => count($countryStats),
            'total_records' => $totalRecords,
            'scope' => $scope,
        ];

        $key = $this->getStatisticsKey('country_year', $scope);

        \App\Models\Statistic::create([
            'database_entity_id' => $empodatEntity->id,
            'key' => $key,
            'meta_data' => $statisticsData,
        ]);

        $scopeLabel = $scope === 'public' ? 'public' : 'admin (all data)';

        return back()->with('success', "Country statistics ({$scopeLabel}) generated and stored successfully.");
    }

    /**
     * Display country year statistics from stored JSON data
     */
    public function viewCountryStats(Request $request)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Determine which scope to display
        $scope = $request->input('scope', 'public');

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $key = $this->getStatisticsKey('country_year', $scope);

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', $key)
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat.statistics.countryYear', [
                'countryStats' => [],
                'totalRecords' => 0,
                'minYear' => date('Y') - 10,
                'maxYear' => date('Y'),
                'displayMinYear' => date('Y') - 10,
                'displayMaxYear' => date('Y'),
                'message' => 'No country statistics data available. Please generate the statistics first.',
                'statistics' => null,
                'scope' => $scope,
                'canViewAll' => $canViewAll,
            ]);
        }

        $countryData = $statisticsRecord->meta_data;
        $countryStats = $countryData['data'];
        $dbMinYear = $countryData['year_range']['min_year'];
        $dbMaxYear = $countryData['year_range']['max_year'];

        // Get display year range from request or use defaults
        $displayMinYear = $request->input('min_year', date('Y') - 10);
        $displayMaxYear = $request->input('max_year', date('Y'));

        // Validate year range
        $displayMinYear = max($displayMinYear, $dbMinYear);
        $displayMaxYear = min($displayMaxYear, $dbMaxYear);

        if ($displayMinYear > $displayMaxYear) {
            $displayMinYear = $displayMaxYear;
        }

        // Filter country stats by display year range
        $filteredCountryStats = [];
        foreach ($countryStats as $country => $yearData) {
            foreach ($yearData as $year => $count) {
                if ($year >= $displayMinYear && $year <= $displayMaxYear) {
                    $filteredCountryStats[$country][$year] = $count;
                }
            }
        }

        // Use stored total_records from generation time
        $totalRecords = $countryData['total_records'] ?? 0;

        return view('empodat.statistics.countryYear', [
            'countryStats' => $filteredCountryStats,
            'totalRecords' => $totalRecords,
            'minYear' => $dbMinYear,
            'maxYear' => $dbMaxYear,
            'displayMinYear' => $displayMinYear,
            'displayMaxYear' => $displayMaxYear,
            'generatedAt' => $countryData['generated_at'] ?? null,
            'statistics' => $statisticsRecord,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Display matrix statistics
     */
    public function matrix(Request $request)
    {
        return $this->viewMatrixStats($request);
    }

    /**
     * Generate and store matrix statistics data
     */
    public function generateMatrixStats(Request $request)
    {
        $scope = $request->input('scope', 'public');

        // Only admins can generate 'all' scope
        if ($scope === 'all' && ! Auth::user()->hasRole('super_admin')) {
            return back()->with('error', 'You do not have permission to generate admin statistics.');
        }

        // Extend execution time and disable memory-consuming features
        set_time_limit(600);
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        // Get matrix statistics with hierarchical structure (filtered by scope)
        $statistics = $this->getStatisticsBaseQuery($scope)
            ->join('list_matrices as lm', 'em.matrix_id', '=', 'lm.id')
            ->select(
                'lm.title',
                'lm.subtitle',
                'lm.type',
                'lm.name as matrix_name',
                'lm.id as matrix_id',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('lm.title', 'lm.subtitle', 'lm.type', 'lm.name', 'lm.id')
            ->orderBy('lm.title')
            ->orderBy('lm.subtitle')
            ->orderBy('lm.type')
            ->get();

        // Transform data into hierarchical structure
        $matrixStats = [];
        $totalRecords = 0;

        foreach ($statistics as $stat) {
            // Build the full hierarchy path
            $hierarchy = [];
            if ($stat->title) {
                $hierarchy[] = $stat->title;
            }
            if ($stat->subtitle) {
                $hierarchy[] = $stat->subtitle;
            }
            if ($stat->type) {
                $hierarchy[] = $stat->type;
            }

            $fullPath = implode(' → ', $hierarchy);
            $level = count($hierarchy);

            $matrixStats[] = [
                'matrix_id' => $stat->matrix_id,
                'matrix_name' => $stat->matrix_name,
                'title' => $stat->title,
                'subtitle' => $stat->subtitle,
                'type' => $stat->type,
                'hierarchy_path' => $fullPath,
                'hierarchy_level' => $level,
                'record_count' => $stat->record_count,
            ];
            $totalRecords += $stat->record_count;
        }

        // Sort by hierarchy path for better organization
        usort($matrixStats, function ($a, $b) {
            return strcmp($a['hierarchy_path'], $b['hierarchy_path']);
        });

        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Create new statistics record
        $statisticsData = [
            'data' => $matrixStats,
            'generated_at' => now()->toISOString(),
            'total_matrices' => count($matrixStats),
            'total_records' => $totalRecords,
            'scope' => $scope,
        ];

        $key = $this->getStatisticsKey('matrix', $scope);

        \App\Models\Statistic::create([
            'database_entity_id' => $empodatEntity->id,
            'key' => $key,
            'meta_data' => $statisticsData,
        ]);

        $scopeLabel = $scope === 'public' ? 'public' : 'admin (all data)';

        return back()->with('success', "Matrix statistics ({$scopeLabel}) generated and stored successfully.");
    }

    /**
     * Display matrix statistics from stored JSON data
     */
    public function viewMatrixStats(?Request $request = null)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Determine which scope to display
        $scope = $request ? $request->input('scope', 'public') : 'public';

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $key = $this->getStatisticsKey('matrix', $scope);

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', $key)
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat.statistics.matrix', [
                'matrixStats' => [],
                'totalRecords' => 0,
                'message' => 'No matrix statistics data available. Please generate the statistics first.',
                'statistics' => null,
                'scope' => $scope,
                'canViewAll' => $canViewAll,
            ]);
        }

        $matrixData = $statisticsRecord->meta_data;
        $matrixStats = $matrixData['data'];

        // Use stored total_records from generation time
        $totalRecords = $matrixData['total_records'] ?? 0;

        return view('empodat.statistics.matrix', [
            'matrixStats' => $matrixStats,
            'totalRecords' => $totalRecords,
            'generatedAt' => $matrixData['generated_at'] ?? null,
            'totalMatrices' => $matrixData['total_matrices'] ?? 0,
            'statistics' => $statisticsRecord,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Download matrix statistics as CSV
     */
    private function downloadMatrixCsv()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return response()->json(['error' => 'Empodat database entity not found.'], 404);
        }

        // Get statistics record
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)->first();

        if (! $statisticsRecord || ! isset($statisticsRecord->meta_data['matrix_statistics'])) {
            return response()->json(['error' => 'No matrix statistics available.'], 404);
        }

        $matrixData = $statisticsRecord->meta_data['matrix_statistics'];
        $matrixStats = $matrixData['data'];

        $csvData = [];
        $csvData[] = ['Title', 'Subtitle', 'Type', 'Matrix Name', 'Hierarchy Path', 'Record Count'];

        foreach ($matrixStats as $stat) {
            $csvData[] = [
                $stat['title'] ?? '',
                $stat['subtitle'] ?? '',
                $stat['type'] ?? '',
                $stat['matrix_name'],
                $stat['hierarchy_path'],
                $stat['record_count'],
            ];
        }

        $filename = 'empodat_matrix_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Display country statistics
     */
    public function country()
    {
        $countryStats = DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('lc.name', 'lc.code', 'lc.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $totalRecords = EmpodatMain::count();

        return view('empodat.statistics.country', [
            'countryStats' => $countryStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Display QA/QC category statistics
     */
    public function qaqc()
    {
        //
    }

    /**
     * Download statistics as CSV
     */
    public function downloadCsv(Request $request)
    {
        $type = $request->input('type', 'countryYear');

        switch ($type) {
            case 'countryYear':
                return $this->downloadCountryYearCsv($request);
            case 'matrix':
                return $this->downloadMatrixCsv();
            case 'substance':
                return $this->downloadSubstanceCsv();
            case 'country':
                return $this->downloadCountryCsv();
            case 'method':
                return $this->downloadMethodCsv();
            case 'quality':
                return $this->downloadQualityCsv();
            default:
                return $this->downloadCountryYearCsv($request);
        }
    }

    /**
     * Download country year statistics as CSV
     */
    private function downloadCountryYearCsv(Request $request)
    {
        // Get the same data as the countryYear method
        $yearRange = EmpodatMain::selectRaw('MIN(sampling_date_year) as min_year, MAX(sampling_date_year) as max_year')
            ->whereNotNull('sampling_date_year')
            ->first();

        $dbMinYear = $yearRange->min_year ?? date('Y') - 10;
        $dbMaxYear = $yearRange->max_year ?? date('Y');

        $displayMinYear = $request->input('min_year', date('Y') - 10);
        $displayMaxYear = $request->input('max_year', date('Y'));

        $displayMinYear = max($displayMinYear, $dbMinYear);
        $displayMaxYear = min($displayMaxYear, $dbMaxYear);

        if ($displayMinYear > $displayMaxYear) {
            $displayMinYear = $displayMaxYear;
        }

        $displayYears = range($displayMinYear, $displayMaxYear);

        $statistics = DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                'em.sampling_date_year',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('em.sampling_date_year')
            ->whereBetween('em.sampling_date_year', [$displayMinYear, $displayMaxYear])
            ->groupBy('lc.name', 'lc.code', 'em.sampling_date_year')
            ->orderBy('lc.name')
            ->orderBy('em.sampling_date_year')
            ->get();

        $countryStats = [];
        foreach ($statistics as $stat) {
            $countryStats[$stat->country_name][$stat->sampling_date_year] = $stat->record_count;
        }

        // Generate CSV content
        $csvData = [];

        // Header row
        $header = ['Country'];
        foreach ($displayYears as $year) {
            $header[] = $year;
        }
        $csvData[] = $header;

        // Data rows
        foreach ($countryStats as $country => $yearData) {
            $row = [$country];
            foreach ($displayYears as $year) {
                $row[] = isset($yearData[$year]) ? $yearData[$year] : '';
            }
            $csvData[] = $row;
        }

        $filename = 'empodat_country_year_statistics_'.$displayMinYear.'_to_'.$displayMaxYear.'_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Download country statistics as CSV
     */
    private function downloadCountryCsv()
    {
        $countryStats = DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('lc.name', 'lc.code', 'lc.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['Country Name', 'Country Code', 'Record Count'];

        foreach ($countryStats as $stat) {
            $csvData[] = [
                $stat->country_name,
                $stat->country_code,
                $stat->record_count,
            ];
        }

        $filename = 'empodat_country_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Download method statistics as CSV
     */
    private function downloadMethodCsv()
    {
        $methodStats = DB::table('empodat_main as em')
            ->join('empodat_analytical_methods as eam', 'em.method_id', '=', 'eam.id')
            ->select(
                'eam.name as method_name',
                'eam.code as method_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('eam.name', 'eam.code', 'eam.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['Method Name', 'Method Code', 'Record Count'];

        foreach ($methodStats as $stat) {
            $csvData[] = [
                $stat->method_name,
                $stat->method_code,
                $stat->record_count,
            ];
        }

        $filename = 'empodat_method_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Generate CSV response
     */
    private function generateCsvResponse($csvData, $filename)
    {
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function ($field) {
                return '"'.str_replace('"', '""', $field).'"';
            }, $row))."\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * Display substance statistics
     */
    public function substance(Request $request)
    {
        return $this->viewSubstanceStats($request);
    }

    /**
     * Generate and store substance statistics data
     */
    public function generateSubstanceStats(Request $request)
    {
        $scope = $request->input('scope', 'public');

        // Only admins can generate 'all' scope
        if ($scope === 'all' && ! Auth::user()->hasRole('super_admin')) {
            return back()->with('error', 'You do not have permission to generate admin statistics.');
        }

        // Extend execution time and disable memory-consuming features
        set_time_limit(600);
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        // Get substance statistics (filtered by scope)
        $statistics = $this->getStatisticsBaseQuery($scope)
            ->join('susdat_substances as ss', 'em.substance_id', '=', 'ss.id')
            ->select(
                'ss.id as substance_id',
                'ss.code as substance_code',
                'ss.name as substance_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('ss.id', 'ss.code', 'ss.name')
            ->orderBy('record_count', 'desc')
            ->get();

        // Transform data into a structure suitable for storage
        $substanceStats = [];
        $totalRecords = 0;

        foreach ($statistics as $stat) {
            // Create substance prefix (NS + code)
            $substancePrefix = 'NS'.$stat->substance_code;

            $substanceStats[] = [
                'substance_id' => $stat->substance_id,
                'substance_prefix' => $substancePrefix,
                'substance_name' => $stat->substance_name,
                'substance_code' => $stat->substance_code,
                'record_count' => $stat->record_count,
            ];
            $totalRecords += $stat->record_count;
        }

        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Create new statistics record
        $statisticsData = [
            'data' => $substanceStats,
            'generated_at' => now()->toISOString(),
            'total_substances' => count($substanceStats),
            'total_records' => $totalRecords,
            'scope' => $scope,
        ];

        $key = $this->getStatisticsKey('substance', $scope);

        \App\Models\Statistic::create([
            'database_entity_id' => $empodatEntity->id,
            'key' => $key,
            'meta_data' => $statisticsData,
        ]);

        $scopeLabel = $scope === 'public' ? 'public' : 'admin (all data)';

        return back()->with('success', "Substance statistics ({$scopeLabel}) generated and stored successfully.");
    }

    /**
     * Display substance statistics from stored JSON data
     */
    public function viewSubstanceStats(?Request $request = null)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Determine which scope to display
        $scope = $request ? $request->input('scope', 'public') : 'public';

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $key = $this->getStatisticsKey('substance', $scope);

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', $key)
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat.statistics.substance', [
                'substanceStats' => [],
                'totalRecords' => 0,
                'message' => 'No substance statistics data available. Please generate the statistics first.',
                'statistics' => null,
                'scope' => $scope,
                'canViewAll' => $canViewAll,
            ]);
        }

        $substanceData = $statisticsRecord->meta_data;
        $substanceStats = $substanceData['data'];

        // Use stored total_records from generation time
        $totalRecords = $substanceData['total_records'] ?? 0;

        return view('empodat.statistics.substance', [
            'substanceStats' => $substanceStats,
            'totalRecords' => $totalRecords,
            'generatedAt' => $substanceData['generated_at'] ?? null,
            'totalSubstances' => $substanceData['total_substances'] ?? 0,
            'statistics' => $statisticsRecord,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Download substance statistics as CSV
     */
    private function downloadSubstanceCsv()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return response()->json(['error' => 'Empodat database entity not found.'], 404);
        }

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', 'substance')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return response()->json(['error' => 'No substance statistics available.'], 404);
        }

        $substanceData = $statisticsRecord->meta_data;
        $substanceStats = $substanceData['data'];

        $csvData = [];
        $csvData[] = ['Substance Prefix', 'Substance Name', 'Record Count'];

        foreach ($substanceStats as $stat) {
            $csvData[] = [
                $stat['substance_prefix'],
                $stat['substance_name'],
                $stat['record_count'],
            ];
        }

        $filename = 'empodat_substance_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Display quality statistics
     */
    public function quality(Request $request)
    {
        return $this->viewQualityStats($request);
    }

    /**
     * Generate and store quality statistics data
     */
    public function generateQualityStats(Request $request)
    {
        $scope = $request->input('scope', 'public');

        // Only admins can generate 'all' scope
        if ($scope === 'all' && ! Auth::user()->hasRole('super_admin')) {
            return back()->with('error', 'You do not have permission to generate admin statistics.');
        }

        // Extend execution time and disable memory-consuming features
        set_time_limit(600);
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        // Get all quality categories
        $qualityCategories = DB::table('list_quality_empodat_analytical_methods')
            ->orderBy('min_rating', 'desc')
            ->get();

        $qualityStats = [];
        $totalRecords = 0;

        foreach ($qualityCategories as $category) {
            // Count records for each quality category (filtered by scope)
            $recordCount = $this->getStatisticsBaseQuery($scope)
                ->join('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
                ->where('eam.rating', '>=', $category->min_rating)
                ->where('eam.rating', '<', $category->max_rating)
                ->count();

            $qualityStats[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'min_rating' => $category->min_rating,
                'max_rating' => $category->max_rating,
                'rating_range' => $category->min_rating.'-'.($category->max_rating - 1),
                'record_count' => $recordCount,
            ];

            $totalRecords += $recordCount;
        }

        // Get records with no rating (NULL or no analytical method) - filtered by scope
        $noRatingCount = $this->getStatisticsBaseQuery($scope)
            ->leftJoin('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
            ->where(function ($query) {
                $query->whereNull('eam.rating')
                    ->orWhereNull('em.method_id');
            })
            ->count();

        if ($noRatingCount > 0) {
            $qualityStats[] = [
                'category_id' => null,
                'category_name' => 'No quality rating available',
                'min_rating' => null,
                'max_rating' => null,
                'rating_range' => 'N/A',
                'record_count' => $noRatingCount,
            ];
            $totalRecords += $noRatingCount;
        }

        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Create new statistics record
        $statisticsData = [
            'data' => $qualityStats,
            'generated_at' => now()->toISOString(),
            'total_categories' => count($qualityStats),
            'total_records' => $totalRecords,
            'scope' => $scope,
        ];

        $key = $this->getStatisticsKey('quality', $scope);

        \App\Models\Statistic::create([
            'database_entity_id' => $empodatEntity->id,
            'key' => $key,
            'meta_data' => $statisticsData,
        ]);

        $scopeLabel = $scope === 'public' ? 'public' : 'admin (all data)';

        return back()->with('success', "Quality statistics ({$scopeLabel}) generated and stored successfully.");
    }

    /**
     * Display quality statistics from stored JSON data
     */
    public function viewQualityStats(?Request $request = null)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Determine which scope to display
        $scope = $request ? $request->input('scope', 'public') : 'public';

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $key = $this->getStatisticsKey('quality', $scope);

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', $key)
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat.statistics.quality', [
                'qualityStats' => [],
                'totalRecords' => 0,
                'message' => 'No quality statistics data available. Please generate the statistics first.',
                'statistics' => null,
                'scope' => $scope,
                'canViewAll' => $canViewAll,
            ]);
        }

        $qualityData = $statisticsRecord->meta_data;
        $qualityStats = $qualityData['data'];

        // Use stored total_records from generation time
        $totalRecords = $qualityData['total_records'] ?? 0;

        return view('empodat.statistics.quality', [
            'qualityStats' => $qualityStats,
            'totalRecords' => $totalRecords,
            'generatedAt' => $qualityData['generated_at'] ?? null,
            'totalCategories' => $qualityData['total_categories'] ?? 0,
            'statistics' => $statisticsRecord,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Download quality statistics as CSV
     */
    private function downloadQualityCsv()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return response()->json(['error' => 'Empodat database entity not found.'], 404);
        }

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', 'quality')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return response()->json(['error' => 'No quality statistics available.'], 404);
        }

        $qualityData = $statisticsRecord->meta_data;
        $qualityStats = $qualityData['data'];

        $csvData = [];
        $csvData[] = ['Quality Category', 'Rating Range', 'Min Rating', 'Max Rating', 'Record Count'];

        foreach ($qualityStats as $stat) {
            $csvData[] = [
                $stat['category_name'],
                $stat['rating_range'],
                $stat['min_rating'] ?? 'N/A',
                $stat['max_rating'] ?? 'N/A',
                $stat['record_count'],
            ];
        }

        $filename = 'empodat_quality_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }
}
