<?php

declare(strict_types=1);

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Literature\LiteratureTempMain;
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
     * Check if user has access to the Literature module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'literature')->first();

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
        if ($user->hasRole('literature')) {
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
        $literatureEntity = DatabaseEntity::where('code', 'literature')->first();
        $allStats = [];

        if ($literatureEntity) {
            $statisticKeys = Statistic::where('database_entity_id', $literatureEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $literatureEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $literatureEntity->number_of_records ?? LiteratureTempMain::count();

        return view('literature.statistics.index', [
            'literatureEntity' => $literatureEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Generate and store all statistics data
     */
    public function generateStatistics()
    {
        $literatureEntity = DatabaseEntity::where('code', 'literature')->first();

        if (! $literatureEntity) {
            return back()->with('error', 'Literature database entity not found.');
        }

        $this->generateCountryStats($literatureEntity);
        $this->generateEcosystemStats($literatureEntity);
        $this->generateClassStats($literatureEntity);
        $this->generateSubstanceStats($literatureEntity);
        $this->generateTotalsStats($literatureEntity);

        return back()->with('success', 'All statistics generated and stored successfully.');
    }

    /**
     * Generate country statistics
     */
    private function generateCountryStats(DatabaseEntity $entity): void
    {
        // Records per country (join on list_countries via country_id)
        $recordsByCountry = DB::table('literature_temp_main as ltm')
            ->join('list_countries as lc', 'ltm.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('ltm.country_id')
            ->groupBy('lc.id', 'lc.name')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByCountryData = [];
        foreach ($recordsByCountry as $stat) {
            $recordsByCountryData[$stat->country_name] = [
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'literature.per_country',
            'meta_data' => [
                'data' => $recordsByCountryData,
                'generated_at' => now()->toISOString(),
                'total_countries' => count($recordsByCountryData),
            ],
        ]);
    }

    /**
     * Generate ecosystem (habitat type) statistics
     */
    private function generateEcosystemStats(DatabaseEntity $entity): void
    {
        // Records per ecosystem/habitat type (join on list_habitat_types via habitat_type_id)
        $recordsByEcosystem = DB::table('literature_temp_main as ltm')
            ->join('list_habitat_types as lht', 'ltm.habitat_type_id', '=', 'lht.id')
            ->select(
                'lht.name as ecosystem_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('ltm.habitat_type_id')
            ->groupBy('lht.id', 'lht.name')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByEcosystemData = [];
        foreach ($recordsByEcosystem as $stat) {
            $recordsByEcosystemData[$stat->ecosystem_name] = [
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'literature.per_ecosystem',
            'meta_data' => [
                'data' => $recordsByEcosystemData,
                'generated_at' => now()->toISOString(),
                'total_ecosystems' => count($recordsByEcosystemData),
            ],
        ]);
    }

    /**
     * Generate class (species class) statistics
     */
    private function generateClassStats(DatabaseEntity $entity): void
    {
        // Records per species class (join on list_species via species_id, group by class)
        $recordsByClass = DB::table('literature_temp_main as ltm')
            ->join('list_species as ls', 'ltm.species_id', '=', 'ls.id')
            ->select(
                'ls.class as class_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('ltm.species_id')
            ->whereNotNull('ls.class')
            ->where('ls.class', '!=', '')
            ->groupBy('ls.class')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByClassData = [];
        foreach ($recordsByClass as $stat) {
            $recordsByClassData[$stat->class_name] = [
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'literature.per_class',
            'meta_data' => [
                'data' => $recordsByClassData,
                'generated_at' => now()->toISOString(),
                'total_classes' => count($recordsByClassData),
            ],
        ]);
    }

    /**
     * Generate substance statistics
     */
    private function generateSubstanceStats(DatabaseEntity $entity): void
    {
        // Records per substance (join on susdat_substances via substance_id)
        $recordsBySubstance = DB::table('literature_temp_main as ltm')
            ->join('susdat_substances as ss', 'ltm.substance_id', '=', 'ss.id')
            ->select(
                'ss.name as substance_name',
                'ss.code as substance_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('ltm.substance_id')
            ->groupBy('ss.id', 'ss.name', 'ss.code')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsBySubstanceData = [];
        foreach ($recordsBySubstance as $stat) {
            $recordsBySubstanceData[$stat->substance_name] = [
                'count' => $stat->record_count,
                'code' => $stat->substance_code,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'literature.per_substance',
            'meta_data' => [
                'data' => $recordsBySubstanceData,
                'generated_at' => now()->toISOString(),
                'total_substances' => count($recordsBySubstanceData),
            ],
        ]);
    }

    /**
     * Generate totals statistics
     */
    private function generateTotalsStats(DatabaseEntity $entity): void
    {
        $totalRecords = LiteratureTempMain::count();

        $totalCountries = LiteratureTempMain::distinct('country_id')
            ->whereNotNull('country_id')
            ->count('country_id');

        $totalEcosystems = LiteratureTempMain::distinct('habitat_type_id')
            ->whereNotNull('habitat_type_id')
            ->count('habitat_type_id');

        $totalClasses = DB::table('literature_temp_main as ltm')
            ->join('list_species as ls', 'ltm.species_id', '=', 'ls.id')
            ->whereNotNull('ls.class')
            ->where('ls.class', '!=', '')
            ->distinct()
            ->count('ls.class');

        $totalSubstances = LiteratureTempMain::distinct('substance_id')
            ->whereNotNull('substance_id')
            ->count('substance_id');

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'literature.totals',
            'meta_data' => [
                'total_records' => $totalRecords,
                'total_countries' => $totalCountries,
                'total_ecosystems' => $totalEcosystems,
                'total_classes' => $totalClasses,
                'total_substances' => $totalSubstances,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Display records by country statistics
     */
    public function perCountry()
    {
        $literatureEntity = DatabaseEntity::where('code', 'literature')->first();

        if (! $literatureEntity) {
            return back()->with('error', 'Literature database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $literatureEntity->id)
            ->where('key', 'literature.per_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('literature.statistics.per_country', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('literature.statistics.per_country', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalCountries' => $statisticsRecord->meta_data['total_countries'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by ecosystem statistics
     */
    public function perEcosystem()
    {
        $literatureEntity = DatabaseEntity::where('code', 'literature')->first();

        if (! $literatureEntity) {
            return back()->with('error', 'Literature database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $literatureEntity->id)
            ->where('key', 'literature.per_ecosystem')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('literature.statistics.per_ecosystem', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('literature.statistics.per_ecosystem', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalEcosystems' => $statisticsRecord->meta_data['total_ecosystems'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by species class statistics
     */
    public function perClass()
    {
        $literatureEntity = DatabaseEntity::where('code', 'literature')->first();

        if (! $literatureEntity) {
            return back()->with('error', 'Literature database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $literatureEntity->id)
            ->where('key', 'literature.per_class')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('literature.statistics.per_class', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('literature.statistics.per_class', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalClasses' => $statisticsRecord->meta_data['total_classes'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by substance statistics
     */
    public function perSubstance()
    {
        $literatureEntity = DatabaseEntity::where('code', 'literature')->first();

        if (! $literatureEntity) {
            return back()->with('error', 'Literature database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $literatureEntity->id)
            ->where('key', 'literature.per_substance')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('literature.statistics.per_substance', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('literature.statistics.per_substance', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalSubstances' => $statisticsRecord->meta_data['total_substances'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }
}
