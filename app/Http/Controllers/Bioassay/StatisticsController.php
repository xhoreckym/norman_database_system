<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bioassay;

use App\Http\Controllers\Controller;
use App\Models\Bioassay\FieldStudy;
use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->checkModuleAccess();
    }

    /**
     * Check if user has access to the Bioassay module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'bioassay')->first();

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
        if ($user->hasRole('bioassay')) {
            return;
        }

        // User doesn't have permission
        abort(403, 'You do not have permission to access this module.');
    }

    /**
     * Display statistics overview page
     */
    public function index()
    {
        $bioassayEntity = DatabaseEntity::where('code', 'bioassay')->first();
        $allStats = [];

        if ($bioassayEntity) {
            $statisticKeys = Statistic::where('database_entity_id', $bioassayEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $bioassayEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $bioassayEntity->number_of_records ?? FieldStudy::count();

        return view('bioassay.statistics.index', [
            'bioassayEntity' => $bioassayEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Generate and store all statistics data
     */
    public function generateStatistics()
    {
        $bioassayEntity = DatabaseEntity::where('code', 'bioassay')->first();

        if (! $bioassayEntity) {
            return back()->with('error', 'Bioassay database entity not found.');
        }

        $this->generateCountryStats($bioassayEntity);
        $this->generateBioassayNameStats($bioassayEntity);
        $this->generateEndpointStats($bioassayEntity);
        $this->generateDeterminandStats($bioassayEntity);
        $this->generateYearStats($bioassayEntity);
        $this->generateTotalsStats($bioassayEntity);

        return back()->with('success', 'All statistics generated and stored successfully.');
    }

    /**
     * Generate country statistics
     */
    private function generateCountryStats(DatabaseEntity $entity): void
    {
        // Records per country (via sample data relationship)
        $recordsByCountry = DB::table('bioassay_field_studies as bfs')
            ->join('bioassay_monitor_sample_data as bmsd', 'bfs.m_sd_id', '=', 'bmsd.id')
            ->join('monitor_x_country as mxc', 'bmsd.m_country_id', '=', 'mxc.id')
            ->select(
                'mxc.name as country_name',
                'mxc.id as country_id',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('bmsd.m_country_id')
            ->groupBy('mxc.name', 'mxc.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByCountryData = [];
        foreach ($recordsByCountry as $stat) {
            $recordsByCountryData[$stat->country_name] = [
                'id' => $stat->country_id,
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'bioassay.per_country',
            'meta_data' => [
                'data' => $recordsByCountryData,
                'generated_at' => now()->toISOString(),
                'total_countries' => count($recordsByCountryData),
            ],
        ]);
    }

    /**
     * Generate bioassay name statistics
     */
    private function generateBioassayNameStats(DatabaseEntity $entity): void
    {
        $recordsByBioassayName = DB::table('bioassay_field_studies as bfs')
            ->join('monitor_x_bioassay_name as mxbn', 'bfs.m_bioassay_name_id', '=', 'mxbn.id')
            ->select(
                'mxbn.name as bioassay_name',
                'mxbn.id as bioassay_name_id',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('bfs.m_bioassay_name_id')
            ->groupBy('mxbn.name', 'mxbn.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByBioassayNameData = [];
        foreach ($recordsByBioassayName as $stat) {
            $recordsByBioassayNameData[$stat->bioassay_name] = [
                'id' => $stat->bioassay_name_id,
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'bioassay.per_bioassay_name',
            'meta_data' => [
                'data' => $recordsByBioassayNameData,
                'generated_at' => now()->toISOString(),
                'total_bioassay_names' => count($recordsByBioassayNameData),
            ],
        ]);
    }

    /**
     * Generate endpoint statistics
     */
    private function generateEndpointStats(DatabaseEntity $entity): void
    {
        $recordsByEndpoint = DB::table('bioassay_field_studies as bfs')
            ->join('monitor_x_endpoint as mxe', 'bfs.m_endpoint_id', '=', 'mxe.id')
            ->select(
                'mxe.name as endpoint_name',
                'mxe.id as endpoint_id',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('bfs.m_endpoint_id')
            ->groupBy('mxe.name', 'mxe.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByEndpointData = [];
        foreach ($recordsByEndpoint as $stat) {
            $recordsByEndpointData[$stat->endpoint_name] = [
                'id' => $stat->endpoint_id,
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'bioassay.per_endpoint',
            'meta_data' => [
                'data' => $recordsByEndpointData,
                'generated_at' => now()->toISOString(),
                'total_endpoints' => count($recordsByEndpointData),
            ],
        ]);
    }

    /**
     * Generate determinand statistics
     */
    private function generateDeterminandStats(DatabaseEntity $entity): void
    {
        $recordsByDeterminand = DB::table('bioassay_field_studies as bfs')
            ->join('monitor_x_main_determinand as mxmd', 'bfs.m_main_determinand_id', '=', 'mxmd.id')
            ->select(
                'mxmd.name as determinand_name',
                'mxmd.id as determinand_id',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('bfs.m_main_determinand_id')
            ->groupBy('mxmd.name', 'mxmd.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByDeterminandData = [];
        foreach ($recordsByDeterminand as $stat) {
            $recordsByDeterminandData[$stat->determinand_name] = [
                'id' => $stat->determinand_id,
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'bioassay.per_determinand',
            'meta_data' => [
                'data' => $recordsByDeterminandData,
                'generated_at' => now()->toISOString(),
                'total_determinands' => count($recordsByDeterminandData),
            ],
        ]);
    }

    /**
     * Generate year statistics
     */
    private function generateYearStats(DatabaseEntity $entity): void
    {
        $recordsByYear = DB::table('bioassay_field_studies')
            ->select(
                'date_performed_year as year',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('date_performed_year')
            ->where('date_performed_year', '>', 0)
            ->groupBy('date_performed_year')
            ->orderBy('date_performed_year', 'desc')
            ->get();

        $recordsByYearData = [];
        foreach ($recordsByYear as $stat) {
            $recordsByYearData[(string) $stat->year] = $stat->record_count;
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'bioassay.per_year',
            'meta_data' => [
                'data' => $recordsByYearData,
                'generated_at' => now()->toISOString(),
                'total_years' => count($recordsByYearData),
            ],
        ]);
    }

    /**
     * Generate totals statistics
     */
    private function generateTotalsStats(DatabaseEntity $entity): void
    {
        $totalRecords = FieldStudy::count();

        $totalCountries = DB::table('bioassay_field_studies as bfs')
            ->join('bioassay_monitor_sample_data as bmsd', 'bfs.m_sd_id', '=', 'bmsd.id')
            ->whereNotNull('bmsd.m_country_id')
            ->distinct('bmsd.m_country_id')
            ->count('bmsd.m_country_id');

        $totalBioassayNames = FieldStudy::distinct('m_bioassay_name_id')
            ->whereNotNull('m_bioassay_name_id')
            ->count('m_bioassay_name_id');

        $totalEndpoints = FieldStudy::distinct('m_endpoint_id')
            ->whereNotNull('m_endpoint_id')
            ->count('m_endpoint_id');

        $totalDeterminands = FieldStudy::distinct('m_main_determinand_id')
            ->whereNotNull('m_main_determinand_id')
            ->count('m_main_determinand_id');

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'bioassay.totals',
            'meta_data' => [
                'total_records' => $totalRecords,
                'total_countries' => $totalCountries,
                'total_bioassay_names' => $totalBioassayNames,
                'total_endpoints' => $totalEndpoints,
                'total_determinands' => $totalDeterminands,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Display records by country statistics
     */
    public function perCountry()
    {
        $bioassayEntity = DatabaseEntity::where('code', 'bioassay')->first();

        if (! $bioassayEntity) {
            return back()->with('error', 'Bioassay database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $bioassayEntity->id)
            ->where('key', 'bioassay.per_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('bioassay.statistics.per_country', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('bioassay.statistics.per_country', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalCountries' => $statisticsRecord->meta_data['total_countries'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by bioassay name statistics
     */
    public function perBioassayName()
    {
        $bioassayEntity = DatabaseEntity::where('code', 'bioassay')->first();

        if (! $bioassayEntity) {
            return back()->with('error', 'Bioassay database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $bioassayEntity->id)
            ->where('key', 'bioassay.per_bioassay_name')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('bioassay.statistics.per_bioassay_name', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('bioassay.statistics.per_bioassay_name', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalBioassayNames' => $statisticsRecord->meta_data['total_bioassay_names'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by endpoint statistics
     */
    public function perEndpoint()
    {
        $bioassayEntity = DatabaseEntity::where('code', 'bioassay')->first();

        if (! $bioassayEntity) {
            return back()->with('error', 'Bioassay database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $bioassayEntity->id)
            ->where('key', 'bioassay.per_endpoint')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('bioassay.statistics.per_endpoint', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('bioassay.statistics.per_endpoint', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalEndpoints' => $statisticsRecord->meta_data['total_endpoints'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by determinand statistics
     */
    public function perDeterminand()
    {
        $bioassayEntity = DatabaseEntity::where('code', 'bioassay')->first();

        if (! $bioassayEntity) {
            return back()->with('error', 'Bioassay database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $bioassayEntity->id)
            ->where('key', 'bioassay.per_determinand')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('bioassay.statistics.per_determinand', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('bioassay.statistics.per_determinand', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalDeterminands' => $statisticsRecord->meta_data['total_determinands'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by year statistics
     */
    public function perYear()
    {
        $bioassayEntity = DatabaseEntity::where('code', 'bioassay')->first();

        if (! $bioassayEntity) {
            return back()->with('error', 'Bioassay database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $bioassayEntity->id)
            ->where('key', 'bioassay.per_year')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('bioassay.statistics.per_year', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('bioassay.statistics.per_year', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalYears' => $statisticsRecord->meta_data['total_years'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }
}
