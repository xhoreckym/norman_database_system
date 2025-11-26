<?php

declare(strict_types=1);

namespace App\Http\Controllers\ARBG;

use App\Http\Controllers\Controller;
use App\Models\ARBG\GeneMain;
use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;

class GeneStatisticsController extends Controller
{
    /**
     * Display statistics overview page
     */
    public function index()
    {
        $geneEntity = DatabaseEntity::where('code', 'arbg.gene')->first();
        $allStats = [];

        if ($geneEntity) {
            $statisticKeys = Statistic::where('database_entity_id', $geneEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $geneEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $geneEntity->number_of_records ?? GeneMain::count();

        return view('arbg.gene.statistics.index', [
            'geneEntity' => $geneEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Generate all statistics
     */
    public function generateAll()
    {
        $this->generateCountryStats();
        $this->generateYearStats();
        $this->generateMatrixStats();
        $this->generateTotalsStats();

        session()->flash('success', 'All gene statistics generated successfully.');

        return redirect()->back();
    }

    /**
     * Generate country statistics
     */
    public function generateCountryStats(): void
    {
        // ARBG uses arbg_data_country table with abbreviation field for country codes
        $statistics = DB::table('arbg_gene_main as gm')
            ->join('arbg_gene_coordinates as gc', 'gm.coordinate_id', '=', 'gc.id')
            ->join('arbg_data_country as dc', 'gc.country_id', '=', 'dc.abbreviation')
            ->select(
                'dc.name as country_name',
                'dc.abbreviation as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('gc.country_id')
            ->where('gc.country_id', '!=', '')
            ->groupBy('dc.name', 'dc.abbreviation')
            ->orderBy('record_count', 'desc')
            ->get();

        $countryStats = [];
        foreach ($statistics as $stat) {
            $countryStats[$stat->country_name] = [
                'code' => $stat->country_code,
                'count' => $stat->record_count,
            ];
        }

        $geneEntity = DatabaseEntity::where('code', 'arbg.gene')->first();

        if ($geneEntity) {
            Statistic::create([
                'database_entity_id' => $geneEntity->id,
                'key' => 'arbg.gene.per_country',
                'meta_data' => [
                    'data' => $countryStats,
                    'generated_at' => now()->toISOString(),
                    'total_countries' => count($countryStats),
                ],
            ]);
        }
    }

    /**
     * Generate year statistics
     */
    public function generateYearStats(): void
    {
        $statistics = DB::table('arbg_gene_main')
            ->select(
                'sampling_date_year as year',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('sampling_date_year')
            ->groupBy('sampling_date_year')
            ->orderBy('sampling_date_year')
            ->get();

        $yearStats = [];
        foreach ($statistics as $stat) {
            $yearStats[$stat->year] = $stat->record_count;
        }

        $geneEntity = DatabaseEntity::where('code', 'arbg.gene')->first();

        if ($geneEntity) {
            Statistic::create([
                'database_entity_id' => $geneEntity->id,
                'key' => 'arbg.gene.per_year',
                'meta_data' => [
                    'data' => $yearStats,
                    'generated_at' => now()->toISOString(),
                    'total_years' => count($yearStats),
                    'year_range' => [
                        'min_year' => ! empty($yearStats) ? min(array_keys($yearStats)) : null,
                        'max_year' => ! empty($yearStats) ? max(array_keys($yearStats)) : null,
                    ],
                ],
            ]);
        }
    }

    /**
     * Generate matrix statistics
     */
    public function generateMatrixStats(): void
    {
        $statistics = DB::table('arbg_gene_main as gm')
            ->join('arbg_data_sample_matrix as sm', 'gm.sample_matrix_id', '=', 'sm.id')
            ->select(
                'sm.id as matrix_id',
                'sm.name as matrix_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('sm.id', 'sm.name')
            ->orderBy('record_count', 'desc')
            ->get();

        $matrixStats = [];
        foreach ($statistics as $stat) {
            $matrixStats[$stat->matrix_name ?? 'Unknown'] = [
                'id' => $stat->matrix_id,
                'count' => $stat->record_count,
            ];
        }

        $geneEntity = DatabaseEntity::where('code', 'arbg.gene')->first();

        if ($geneEntity) {
            Statistic::create([
                'database_entity_id' => $geneEntity->id,
                'key' => 'arbg.gene.per_matrix',
                'meta_data' => [
                    'data' => $matrixStats,
                    'generated_at' => now()->toISOString(),
                    'total_matrices' => count($matrixStats),
                ],
            ]);
        }
    }

    /**
     * Generate totals statistics
     */
    public function generateTotalsStats(): void
    {
        $totalRecords = GeneMain::count();
        $totalCountries = DB::table('arbg_gene_main as gm')
            ->join('arbg_gene_coordinates as gc', 'gm.coordinate_id', '=', 'gc.id')
            ->distinct()
            ->count('gc.country_id');
        $totalMatrices = DB::table('arbg_gene_main')
            ->distinct()
            ->count('sample_matrix_id');
        $totalYears = DB::table('arbg_gene_main')
            ->whereNotNull('sampling_date_year')
            ->distinct()
            ->count('sampling_date_year');

        $geneEntity = DatabaseEntity::where('code', 'arbg.gene')->first();

        if ($geneEntity) {
            Statistic::create([
                'database_entity_id' => $geneEntity->id,
                'key' => 'arbg.gene.totals',
                'meta_data' => [
                    'total_records' => $totalRecords,
                    'total_countries' => $totalCountries,
                    'total_matrices' => $totalMatrices,
                    'total_years' => $totalYears,
                    'generated_at' => now()->toISOString(),
                ],
            ]);
        }
    }

    /**
     * View country statistics
     */
    public function perCountry()
    {
        $geneEntity = DatabaseEntity::where('code', 'arbg.gene')->first();

        if (! $geneEntity) {
            return back()->with('error', 'Gene database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $geneEntity->id)
            ->where('key', 'arbg.gene.per_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('arbg.gene.statistics.per_country', [
                'data' => [],
                'totalCountries' => 0,
                'message' => 'No country statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('arbg.gene.statistics.per_country', [
            'data' => $data['data'],
            'totalCountries' => $data['total_countries'],
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }

    /**
     * View year statistics
     */
    public function perYear()
    {
        $geneEntity = DatabaseEntity::where('code', 'arbg.gene')->first();

        if (! $geneEntity) {
            return back()->with('error', 'Gene database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $geneEntity->id)
            ->where('key', 'arbg.gene.per_year')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('arbg.gene.statistics.per_year', [
                'data' => [],
                'totalYears' => 0,
                'message' => 'No year statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('arbg.gene.statistics.per_year', [
            'data' => $data['data'],
            'totalYears' => $data['total_years'],
            'yearRange' => $data['year_range'] ?? null,
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }

    /**
     * View matrix statistics
     */
    public function perMatrix()
    {
        $geneEntity = DatabaseEntity::where('code', 'arbg.gene')->first();

        if (! $geneEntity) {
            return back()->with('error', 'Gene database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $geneEntity->id)
            ->where('key', 'arbg.gene.per_matrix')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('arbg.gene.statistics.per_matrix', [
                'data' => [],
                'totalMatrices' => 0,
                'message' => 'No matrix statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('arbg.gene.statistics.per_matrix', [
            'data' => $data['data'],
            'totalMatrices' => $data['total_matrices'],
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }
}
