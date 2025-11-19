<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshEmpodatSuspectFilters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empodat-suspect:refresh-filters
                            {--force : Force non-concurrent refresh (faster but blocks reads)}
                            {--create : Create the view if it doesn\'t exist}
                            {--stats : Show statistics after refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the Empodat Suspect station filters materialized view';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║  Empodat Suspect - Refresh Station Filters Materialized View ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        try {
            $startTime = microtime(true);

            // Check if the materialized view exists
            $viewExists = $this->checkViewExists();

            if (!$viewExists) {
                if ($this->option('create')) {
                    $this->warn('⚠ Materialized view does not exist. Creating it...');
                    $this->createView();
                } else {
                    $this->error('✗ Materialized view does not exist!');
                    $this->info('Run with --create option to create it:');
                    $this->info('  php artisan empodat-suspect:refresh-filters --create');
                    $this->newLine();
                    $this->info('Or run the seeder:');
                    $this->info('  php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\CreateEmpodatSuspectMaterializedViewSeeder');
                    return Command::FAILURE;
                }
            } else {
                $this->refreshView();
            }

            $duration = round(microtime(true) - $startTime, 2);

            // Show statistics if requested
            if ($this->option('stats')) {
                $this->newLine();
                $this->showStatistics();
            }

            $this->newLine();
            $this->info("✓ Materialized view is ready! (completed in {$duration}s)");

            Log::info('Empodat Suspect filters refreshed successfully', [
                'duration' => $duration,
                'method' => $viewExists ? 'refresh' : 'create'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Failed to refresh materialized view:');
            $this->error('  ' . $e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->error($e->getTraceAsString());
            }

            Log::error('Empodat Suspect filters refresh failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Check if the materialized view exists
     */
    private function checkViewExists(): bool
    {
        $this->info('→ Checking if materialized view exists...');

        $result = DB::select("
            SELECT EXISTS (
                SELECT FROM pg_matviews
                WHERE schemaname = 'public'
                AND matviewname = 'empodat_suspect_station_filters'
            ) as exists
        ");

        $exists = $result[0]->exists ?? false;

        if ($exists) {
            $this->info('  ✓ Materialized view exists');
        } else {
            $this->warn('  ✗ Materialized view does not exist');
        }

        return $exists;
    }

    /**
     * Refresh the existing materialized view
     */
    private function refreshView(): void
    {
        $concurrent = !$this->option('force');

        if ($concurrent) {
            $this->info('→ Refreshing materialized view (CONCURRENT mode - non-blocking)...');
            $this->line('  This allows reads during refresh but may take longer.');

            $refreshStart = microtime(true);
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY empodat_suspect_station_filters');
            $refreshDuration = round(microtime(true) - $refreshStart, 2);

            $this->info("  ✓ Concurrent refresh complete in {$refreshDuration}s");
        } else {
            $this->warn('→ Refreshing materialized view (FORCE mode - blocking)...');
            $this->line('  This will block all reads during refresh but is faster.');

            if (!$this->confirm('Continue with blocking refresh?', false)) {
                $this->info('  Cancelled. Use without --force for non-blocking refresh.');
                exit(Command::SUCCESS);
            }

            $refreshStart = microtime(true);
            DB::statement('REFRESH MATERIALIZED VIEW empodat_suspect_station_filters');
            $refreshDuration = round(microtime(true) - $refreshStart, 2);

            $this->info("  ✓ Forced refresh complete in {$refreshDuration}s");
        }
    }

    /**
     * Create the materialized view from scratch
     */
    private function createView(): void
    {
        // Drop if exists
        $this->info('→ Dropping existing view (if any)...');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_station_filters CASCADE');

        // Create the materialized view
        $this->info('→ Creating materialized view...');
        DB::statement("
            CREATE MATERIALIZED VIEW empodat_suspect_station_filters AS
            SELECT DISTINCT
                esm.station_id,
                es.country_id,
                em.matrix_id,
                em.substance_id,
                em.sampling_date_year,
                em.concentration_indicator_id,
                em.data_source_id,
                em.method_id,
                MAX(esm.ip_max) as max_ip_confidence,
                COUNT(DISTINCT esm.id) as suspect_measurement_count,
                TRUE as has_suspect_data
            FROM empodat_suspect_main esm
            INNER JOIN empodat_stations es ON esm.station_id = es.id
            INNER JOIN empodat_main em ON em.station_id = es.id
            WHERE esm.station_id IS NOT NULL
                AND es.id IS NOT NULL
                AND em.id IS NOT NULL
            GROUP BY
                esm.station_id,
                es.country_id,
                em.matrix_id,
                em.substance_id,
                em.sampling_date_year,
                em.concentration_indicator_id,
                em.data_source_id,
                em.method_id
        ");

        $this->info('  ✓ Materialized view created');

        // Create indexes
        $this->info('→ Creating indexes...');
        $this->createIndexes();

        // Add comment
        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_station_filters IS
            'Lightweight materialized view containing filter metadata for Empodat Suspect searches.
            Refresh command: php artisan empodat-suspect:refresh-filters'
        ");

        $this->info('  ✓ View setup complete');
    }

    /**
     * Create all required indexes on the materialized view
     */
    private function createIndexes(): void
    {
        $indexes = [
            'idx_essf_station_id' => 'station_id',
            'idx_essf_country_id' => 'country_id',
            'idx_essf_matrix_id' => 'matrix_id',
            'idx_essf_substance_id' => 'substance_id',
            'idx_essf_year' => 'sampling_date_year',
            'idx_essf_conc_indicator' => 'concentration_indicator_id',
            'idx_essf_data_source' => 'data_source_id',
            'idx_essf_method' => 'method_id',
        ];

        $indexCount = 0;
        foreach ($indexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_station_filters({$column})");
            $indexCount++;
        }

        // Compound indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_essf_station_substance ON empodat_suspect_station_filters(station_id, substance_id)');
        $indexCount++;

        // UNIQUE index for CONCURRENT refresh support
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_essf_unique_combo
            ON empodat_suspect_station_filters(
                station_id,
                country_id,
                matrix_id,
                substance_id,
                sampling_date_year,
                concentration_indicator_id,
                data_source_id,
                method_id
            )
        ');
        $indexCount++;

        $this->info("  ✓ {$indexCount} indexes created");
    }

    /**
     * Show statistics about the materialized view
     */
    private function showStatistics(): void
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║         View Statistics                ║');
        $this->info('╚════════════════════════════════════════╝');

        try {
            // Get row count
            $rowCount = DB::table('empodat_suspect_station_filters')->count();
            $this->line("  Total filter combinations: " . number_format($rowCount));

            // Get unique stations
            $stationCount = DB::table('empodat_suspect_station_filters')
                ->distinct('station_id')
                ->count('station_id');
            $this->line("  Unique stations:          " . number_format($stationCount));

            // Get unique countries
            $countryCount = DB::table('empodat_suspect_station_filters')
                ->distinct('country_id')
                ->count('country_id');
            $this->line("  Unique countries:         " . number_format($countryCount));

            // Get unique substances
            $substanceCount = DB::table('empodat_suspect_station_filters')
                ->distinct('substance_id')
                ->count('substance_id');
            $this->line("  Unique substances:        " . number_format($substanceCount));

            // Get view size
            $sizeResult = DB::select("
                SELECT pg_size_pretty(pg_total_relation_size('empodat_suspect_station_filters')) as size
            ");
            $this->line("  Total size (with indexes): " . ($sizeResult[0]->size ?? 'N/A'));

            // Get last refresh time
            $mvInfo = DB::select("
                SELECT schemaname, matviewname,
                       pg_size_pretty(pg_relation_size(schemaname||'.'||matviewname)) as size
                FROM pg_matviews
                WHERE matviewname = 'empodat_suspect_station_filters'
            ");

            if (!empty($mvInfo)) {
                $this->line("  View size (data only):     " . ($mvInfo[0]->size ?? 'N/A'));
            }

        } catch (\Exception $e) {
            $this->warn('  Could not retrieve all statistics: ' . $e->getMessage());
        }
    }
}
