<?php

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateEmpodatSuspectMaterializedViewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates or refreshes the empodat_suspect_station_filters materialized view.
     * Safe to run on both local and production environments.
     */
    public function run(): void
    {
        $this->command->info('Starting Empodat Suspect Materialized View creation/refresh...');

        try {
            // Check if the materialized view already exists
            $viewExists = $this->checkViewExists();

            if ($viewExists) {
                $this->command->info('Materialized view exists. Refreshing...');
                $this->refreshView();
            } else {
                $this->command->info('Materialized view does not exist. Creating...');
                $this->createView();
            }

            $this->command->info('✓ Empodat Suspect Materialized View is ready!');

        } catch (\Exception $e) {
            $this->command->error('✗ Failed to create/refresh materialized view: ' . $e->getMessage());
            Log::error('Empodat Suspect MV Seeder failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Check if the materialized view exists
     */
    private function checkViewExists(): bool
    {
        $result = DB::select("
            SELECT EXISTS (
                SELECT FROM pg_matviews
                WHERE schemaname = 'public'
                AND matviewname = 'empodat_suspect_station_filters'
            ) as exists
        ");

        return $result[0]->exists ?? false;
    }

    /**
     * Refresh the existing materialized view
     */
    private function refreshView(): void
    {
        $this->command->info('  → Using CONCURRENT refresh (non-blocking)...');

        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY empodat_suspect_station_filters');

        $this->command->info('  → Refresh complete!');
    }

    /**
     * Create the materialized view from scratch
     */
    private function createView(): void
    {
        // Drop if exists (for idempotency)
        $this->command->info('  → Dropping existing view (if any)...');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_station_filters CASCADE');

        // Create the materialized view
        $this->command->info('  → Creating materialized view...');
        DB::statement("
            CREATE MATERIALIZED VIEW empodat_suspect_station_filters AS
            SELECT DISTINCT
                -- Primary key: station_id is the core linking field
                esm.station_id,

                -- Filter fields from empodat_stations (via join)
                es.country_id,

                -- Filter fields from empodat_main (via station link)
                em.matrix_id,
                em.substance_id,
                em.sampling_date_year,
                em.concentration_indicator_id,
                em.data_source_id,
                em.method_id,

                -- Metadata for convenience
                MAX(esm.ip_max) as max_ip_confidence,
                COUNT(DISTINCT esm.id) as suspect_measurement_count,
                TRUE as has_suspect_data

            FROM empodat_suspect_main esm

            -- Join to stations to get country_id
            INNER JOIN empodat_stations es
                ON esm.station_id = es.id

            -- Join to empodat_main to get all filter fields
            INNER JOIN empodat_main em
                ON em.station_id = es.id

            -- Only include stations that have actual suspect measurements
            WHERE esm.station_id IS NOT NULL
                AND es.id IS NOT NULL
                AND em.id IS NOT NULL

            -- Group by all filter fields to get DISTINCT combinations
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

        // Create indexes
        $this->command->info('  → Creating indexes...');
        $this->createIndexes();

        // Add comment
        $this->command->info('  → Adding documentation comment...');
        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_station_filters IS
            'Lightweight materialized view containing filter metadata for Empodat Suspect searches.
            Contains DISTINCT station/filter combinations for fast filtering.
            Refresh after data imports using: php artisan empodat-suspect:refresh-filters
            Created by seeder. Update frequency: After each data import.'
        ");

        $this->command->info('  → View created successfully!');
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

        foreach ($indexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_station_filters({$column})");
        }

        // Compound indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_essf_station_substance ON empodat_suspect_station_filters(station_id, substance_id)');

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

        $this->command->info('  → ' . (count($indexes) + 2) . ' indexes created!');
    }
}
