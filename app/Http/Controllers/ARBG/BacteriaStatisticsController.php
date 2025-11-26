<?php

declare(strict_types=1);

namespace App\Http\Controllers\ARBG;

use App\Http\Controllers\Controller;
use App\Models\ARBG\BacteriaMain;
use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;

class BacteriaStatisticsController extends Controller
{
    /**
     * Display statistics overview page
     */
    public function index()
    {
        $bacteriaEntity = DatabaseEntity::where('code', 'arbg.bacteria')->first();
        $allStats = [];

        if ($bacteriaEntity) {
            $statisticKeys = Statistic::where('database_entity_id', $bacteriaEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $bacteriaEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $bacteriaEntity->number_of_records ?? BacteriaMain::count();

        return view('arbg.bacteria.statistics.index', [
            'bacteriaEntity' => $bacteriaEntity,
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

        session()->flash('success', 'All bacteria statistics generated successfully.');

        return redirect()->back();
    }

    /**
     * Generate country statistics
     */
    public function generateCountryStats(): void
    {
        // ARBG uses arbg_data_country table with abbreviation field for country codes
        $statistics = DB::table('arbg_bacteria_main as bm')
            ->join('arbg_bacteria_coordinates as bc', 'bm.coordinate_id', '=', 'bc.id')
            ->join('arbg_data_country as dc', 'bc.country_id', '=', 'dc.abbreviation')
            ->select(
                'dc.name as country_name',
                'dc.abbreviation as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('bc.country_id')
            ->where('bc.country_id', '!=', '')
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

        $bacteriaEntity = DatabaseEntity::where('code', 'arbg.bacteria')->first();

        if ($bacteriaEntity) {
            Statistic::create([
                'database_entity_id' => $bacteriaEntity->id,
                'key' => 'arbg.bacteria.per_country',
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
        $statistics = DB::table('arbg_bacteria_main')
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

        $bacteriaEntity = DatabaseEntity::where('code', 'arbg.bacteria')->first();

        if ($bacteriaEntity) {
            Statistic::create([
                'database_entity_id' => $bacteriaEntity->id,
                'key' => 'arbg.bacteria.per_year',
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
        $statistics = DB::table('arbg_bacteria_main as bm')
            ->join('arbg_data_sample_matrix as sm', 'bm.sample_matrix_id', '=', 'sm.id')
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

        $bacteriaEntity = DatabaseEntity::where('code', 'arbg.bacteria')->first();

        if ($bacteriaEntity) {
            Statistic::create([
                'database_entity_id' => $bacteriaEntity->id,
                'key' => 'arbg.bacteria.per_matrix',
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
        $totalRecords = BacteriaMain::count();
        $totalCountries = DB::table('arbg_bacteria_main as bm')
            ->join('arbg_bacteria_coordinates as bc', 'bm.coordinate_id', '=', 'bc.id')
            ->distinct()
            ->count('bc.country_id');
        $totalMatrices = DB::table('arbg_bacteria_main')
            ->distinct()
            ->count('sample_matrix_id');
        $totalYears = DB::table('arbg_bacteria_main')
            ->whereNotNull('sampling_date_year')
            ->distinct()
            ->count('sampling_date_year');

        $bacteriaEntity = DatabaseEntity::where('code', 'arbg.bacteria')->first();

        if ($bacteriaEntity) {
            Statistic::create([
                'database_entity_id' => $bacteriaEntity->id,
                'key' => 'arbg.bacteria.totals',
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
        $bacteriaEntity = DatabaseEntity::where('code', 'arbg.bacteria')->first();

        if (! $bacteriaEntity) {
            return back()->with('error', 'Bacteria database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $bacteriaEntity->id)
            ->where('key', 'arbg.bacteria.per_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('arbg.bacteria.statistics.per_country', [
                'data' => [],
                'totalCountries' => 0,
                'message' => 'No country statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('arbg.bacteria.statistics.per_country', [
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
        $bacteriaEntity = DatabaseEntity::where('code', 'arbg.bacteria')->first();

        if (! $bacteriaEntity) {
            return back()->with('error', 'Bacteria database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $bacteriaEntity->id)
            ->where('key', 'arbg.bacteria.per_year')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('arbg.bacteria.statistics.per_year', [
                'data' => [],
                'totalYears' => 0,
                'message' => 'No year statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('arbg.bacteria.statistics.per_year', [
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
        $bacteriaEntity = DatabaseEntity::where('code', 'arbg.bacteria')->first();

        if (! $bacteriaEntity) {
            return back()->with('error', 'Bacteria database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $bacteriaEntity->id)
            ->where('key', 'arbg.bacteria.per_matrix')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('arbg.bacteria.statistics.per_matrix', [
                'data' => [],
                'totalMatrices' => 0,
                'message' => 'No matrix statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('arbg.bacteria.statistics.per_matrix', [
            'data' => $data['data'],
            'totalMatrices' => $data['total_matrices'],
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }
}
