<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Recreates the empodat_suspect_prioritisation materialized view
     * after removing created_at/updated_at from empodat_suspect_main.
     *
     * Note: This migration uses a fixed limit of 100000 records.
     * For different limits, use: php artisan empodat-suspect:refresh-prioritisation --create --limit=N
     */
    public function up(): void
    {
        $limit = 100000;

        DB::statement("
            CREATE MATERIALIZED VIEW empodat_suspect_prioritisation AS
            WITH limited_suspect AS (
                -- Limit to first N records for testing
                SELECT * FROM empodat_suspect_main
                WHERE id <= {$limit}
            ),
            most_recent_main AS (
                -- Get most recent empodat_main record per station
                -- This prevents Cartesian product and massive memory usage
                SELECT DISTINCT ON (station_id)
                    id,
                    station_id,
                    matrix_id,
                    sampling_date_year
                FROM empodat_main
                WHERE station_id IN (SELECT DISTINCT station_id FROM limited_suspect)
                ORDER BY station_id, sampling_date_year DESC NULLS LAST
            )
            SELECT
                -- Primary identifiers
                esm.id,
                em.id as empodat_main_id,

                -- Core fields from suspect data
                em.matrix_id,
                esm.concentration as concentration_value,
                esm.ip_max,

                -- Geographic and temporal information
                es.country as country,
                em.sampling_date_year as sampling_date_y,
                es.latitude as latitude_decimal,
                es.longitude as longitude_decimal,

                -- Substance information
                ss.code as sus_id,

                -- Matrix-specific fields using COALESCE
                -- basin_name (from biota or water_waste)
                COALESCE(
                    emb.basin_name,
                    emww.basin_name,
                    emws.basin_name,
                    emwg.basin_name
                ) as basin_name,

                -- df_id (from water_waste only)
                emww.df_id,

                -- dsa_id (from water_waste)
                emww.dsa_id,

                -- dsgr_id (from biota)
                emb.dsgr_id,

                -- dtiel_id (from biota)
                emb.dtiel_id,

                -- dmeas_id (from biota)
                emb.dmeas_id

            FROM limited_suspect esm

            -- Join to stations for geographic data
            INNER JOIN empodat_stations es
                ON esm.station_id = es.id

            -- Join to most recent empodat_main record per station
            LEFT JOIN most_recent_main em
                ON em.station_id = esm.station_id

            -- Join to substances for substance code
            LEFT JOIN susdat_substances ss
                ON esm.substance_id = ss.id

            -- Matrix-specific LEFT JOINs based on matrix_id ranges

            -- Biota (matrix_id: 39-47)
            LEFT JOIN empodat_matrix_biota emb
                ON em.id = emb.id
                AND em.matrix_id BETWEEN 39 AND 47

            -- Water waste (matrix_id: 72-74)
            LEFT JOIN empodat_matrix_water_waste emww
                ON em.id = emww.id
                AND em.matrix_id BETWEEN 72 AND 74

            -- Water surface (matrix_id: specific IDs)
            LEFT JOIN empodat_matrix_water_surface emws
                ON em.id = emws.id
                AND em.matrix_id IN (2,3,4,5,6,7,8)

            -- Water ground (matrix_id: 1)
            LEFT JOIN empodat_matrix_water_ground emwg
                ON em.id = emwg.id
                AND em.matrix_id = 1

            WHERE esm.station_id IS NOT NULL
                AND es.id IS NOT NULL
                AND em.id IS NOT NULL
        ");

        // Create indexes
        $this->createIndexes();

        // Add comment
        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_prioritisation IS
            'Comprehensive materialized view for Empodat Suspect prioritisation analysis.
            Combines suspect screening data with matrix-specific metadata.
            Limited to first {$limit} suspect records for testing.
            Refresh command: php artisan empodat-suspect:refresh-prioritisation'
        ");
    }

    /**
     * Create all required indexes on the materialized view
     */
    private function createIndexes(): void
    {
        $indexes = [
            'idx_esp_id' => 'id',
            'idx_esp_empodat_main_id' => 'empodat_main_id',
            'idx_esp_matrix_id' => 'matrix_id',
            'idx_esp_country' => 'country',
            'idx_esp_year' => 'sampling_date_y',
            'idx_esp_sus_id' => 'sus_id',
            'idx_esp_ip_max' => 'ip_max',
        ];

        foreach ($indexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_prioritisation({$column})");
        }

        // Partial indexes for matrix-specific fields
        $partialIndexes = [
            'idx_esp_basin_name' => 'basin_name',
            'idx_esp_df_id' => 'df_id',
            'idx_esp_dsa_id' => 'dsa_id',
            'idx_esp_dsgr_id' => 'dsgr_id',
            'idx_esp_dtiel_id' => 'dtiel_id',
            'idx_esp_dmeas_id' => 'dmeas_id',
        ];

        foreach ($partialIndexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_prioritisation({$column}) WHERE {$column} IS NOT NULL");
        }

        // Compound indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_matrix_year ON empodat_suspect_prioritisation(matrix_id, sampling_date_y)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_country_matrix ON empodat_suspect_prioritisation(country, matrix_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_lat_lon ON empodat_suspect_prioritisation(latitude_decimal, longitude_decimal)');

        // UNIQUE index for CONCURRENT refresh support
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_esp_unique_combo
            ON empodat_suspect_prioritisation(
                id,
                empodat_main_id
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_prioritisation CASCADE');
    }
};
