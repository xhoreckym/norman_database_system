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

            // --create flag: drop and recreate (useful when schema changed or lock issues)
            if ($this->option('create')) {
                $this->warn('⚠ Dropping and recreating materialized view...');
                $this->createView();
            } elseif (!$viewExists) {
                $this->error('✗ Materialized view does not exist!');
                $this->info('Run with --create option to create it:');
                $this->info('  php artisan empodat-suspect:refresh-filters --create');
                return Command::FAILURE;
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
     *
     * OPTIMIZED: Uses a permanent helper table to store station IDs, then creates MV.
     * PostgreSQL doesn't allow temp tables in MV definitions.
     */
    private function createView(): void
    {
        // Drop if exists
        $this->info('→ Dropping existing view (if any)...');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_station_filters CASCADE');

        // Step 1: Create a permanent helper table with suspect station data
        $this->info('→ Step 1: Building suspect stations list...');
        $step1Start = microtime(true);

        DB::statement('DROP TABLE IF EXISTS empodat_suspect_stations_helper CASCADE');
        DB::statement("
            CREATE TABLE empodat_suspect_stations_helper AS
            SELECT DISTINCT
                esm.station_id,
                es.country_id,
                MAX(esm.ip_max) as max_ip_confidence,
                COUNT(esm.id) as suspect_measurement_count
            FROM empodat_suspect_main esm
            INNER JOIN empodat_stations es ON esm.station_id = es.id
            WHERE esm.station_id IS NOT NULL
            GROUP BY esm.station_id, es.country_id
        ");
        DB::statement('CREATE INDEX idx_essh_station_id ON empodat_suspect_stations_helper(station_id)');

        $step1Duration = round(microtime(true) - $step1Start, 2);
        $stationCount = DB::table('empodat_suspect_stations_helper')->count();
        $this->info("  ✓ Found {$stationCount} stations with suspect data ({$step1Duration}s)");

        // Step 2: Create the materialized view by joining with empodat_main
        $this->info('→ Step 2: Building filter combinations from empodat_main...');
        $step2Start = microtime(true);

        DB::statement("
            CREATE MATERIALIZED VIEW empodat_suspect_station_filters AS
            SELECT DISTINCT
                ss.station_id,
                ss.country_id,
                em.matrix_id,
                em.sampling_date_year,
                ss.max_ip_confidence,
                ss.suspect_measurement_count,
                TRUE as has_suspect_data
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            WHERE em.matrix_id IS NOT NULL
        ");

        $step2Duration = round(microtime(true) - $step2Start, 2);
        $this->info("  ✓ Materialized view created ({$step2Duration}s)");

        // Create indexes
        $this->info('→ Creating indexes...');
        $this->createIndexes();

        // Add comment
        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_station_filters IS
            'Optimized filter view for Empodat Suspect searches. Contains station/country/matrix/year.
            Refresh command: php artisan empodat-suspect:refresh-filters
            Helper table: empodat_suspect_stations_helper'
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
            'idx_essf_year' => 'sampling_date_year',
        ];

        $indexCount = 0;
        foreach ($indexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_station_filters({$column})");
            $indexCount++;
        }

        // UNIQUE index for CONCURRENT refresh support
        // Use COALESCE to handle NULL values in the unique constraint
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_essf_unique_combo
            ON empodat_suspect_station_filters(
                station_id,
                COALESCE(country_id, 0),
                COALESCE(matrix_id, 0),
                COALESCE(sampling_date_year, 0)
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

            // Get unique matrices
            $matrixCount = DB::table('empodat_suspect_station_filters')
                ->distinct('matrix_id')
                ->count('matrix_id');
            $this->line("  Unique matrices:          " . number_format($matrixCount));

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
