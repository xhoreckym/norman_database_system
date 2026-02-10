<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Recreates the empodat_suspect_prioritisation materialized view with updated columns:
     * - matrix_id renamed to matrix
     * - Added am_loq (minimum concentration / 3 for given matrix and substance)
     * - Added station_name (stores station_id value)
     * - Added max_ip_max (maximum ip_max for given substance and matrix)
     */
    public function up(): void
    {
        // Drop existing view
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_prioritisation CASCADE');

        $limit = 100000;

        DB::statement("
            CREATE MATERIALIZED VIEW empodat_suspect_prioritisation AS
            WITH limited_suspect AS (
                -- Limit to first N records
                SELECT * FROM empodat_suspect_main
                ORDER BY id
                LIMIT {$limit}
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
            ),
            -- Calculate am_loq: minimum concentration divided by 3 for each matrix + substance combination
            min_concentration_by_matrix_substance AS (
                SELECT
                    em.matrix_id,
                    esm.substance_id,
                    MIN(esm.concentration) / 3.0 AS am_loq
                FROM limited_suspect esm
                INNER JOIN most_recent_main em ON em.station_id = esm.station_id
                WHERE esm.concentration IS NOT NULL
                    AND esm.concentration > 0
                GROUP BY em.matrix_id, esm.substance_id
            ),
            -- Calculate max_ip_max: maximum ip_max for each matrix + substance combination
            max_ip_by_matrix_substance AS (
                SELECT
                    em.matrix_id,
                    esm.substance_id,
                    MAX(esm.ip_max) AS max_ip_max
                FROM limited_suspect esm
                INNER JOIN most_recent_main em ON em.station_id = esm.station_id
                WHERE esm.ip_max IS NOT NULL
                GROUP BY em.matrix_id, esm.substance_id
            )
            SELECT
                -- Primary identifiers
                esm.id,

                -- Core fields from suspect data
                em.matrix_id AS matrix,
                esm.concentration AS concentration_value,
                mcms.am_loq,
                esm.ip_max,
                mims.max_ip_max,

                -- Geographic and temporal information
                es.country AS country,
                esm.station_id AS station_name,
                em.sampling_date_year AS sampling_date_y,
                es.latitude AS latitude_decimal,
                es.longitude AS longitude_decimal,

                -- Substance information
                ss.code AS sus_id,

                -- Matrix-specific fields using COALESCE
                -- basin_name (from biota or water_waste)
                COALESCE(
                    emb.basin_name,
                    emww.basin_name,
                    emws.basin_name,
                    emwg.basin_name
                ) AS basin_name,

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

            -- Join to pre-calculated am_loq
            LEFT JOIN min_concentration_by_matrix_substance mcms
                ON em.matrix_id = mcms.matrix_id
                AND esm.substance_id = mcms.substance_id

            -- Join to pre-calculated max_ip_max
            LEFT JOIN max_ip_by_matrix_substance mims
                ON em.matrix_id = mims.matrix_id
                AND esm.substance_id = mims.substance_id

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
            Includes am_loq (min concentration / 3 per matrix+substance) and max_ip_max.
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
            'idx_esp_matrix' => 'matrix',
            'idx_esp_country' => 'country',
            'idx_esp_year' => 'sampling_date_y',
            'idx_esp_sus_id' => 'sus_id',
            'idx_esp_ip_max' => 'ip_max',
            'idx_esp_station_name' => 'station_name',
            'idx_esp_max_ip_max' => 'max_ip_max',
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
            'idx_esp_am_loq' => 'am_loq',
        ];

        foreach ($partialIndexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_prioritisation({$column}) WHERE {$column} IS NOT NULL");
        }

        // Compound indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_matrix_year ON empodat_suspect_prioritisation(matrix, sampling_date_y)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_country_matrix ON empodat_suspect_prioritisation(country, matrix)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_lat_lon ON empodat_suspect_prioritisation(latitude_decimal, longitude_decimal)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_matrix_substance ON empodat_suspect_prioritisation(matrix, sus_id)');

        // UNIQUE index for CONCURRENT refresh support
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_esp_unique_id
            ON empodat_suspect_prioritisation(id)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the updated view
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_prioritisation CASCADE');

        // Recreate the previous version
        $limit = 100000;

        DB::statement("
            CREATE MATERIALIZED VIEW empodat_suspect_prioritisation AS
            WITH limited_suspect AS (
                SELECT * FROM empodat_suspect_main
                WHERE id <= {$limit}
            ),
            most_recent_main AS (
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
                esm.id,
                em.id as empodat_main_id,
                em.matrix_id,
                esm.concentration as concentration_value,
                esm.ip_max,
                es.country as country,
                em.sampling_date_year as sampling_date_y,
                es.latitude as latitude_decimal,
                es.longitude as longitude_decimal,
                ss.code as sus_id,
                COALESCE(
                    emb.basin_name,
                    emww.basin_name,
                    emws.basin_name,
                    emwg.basin_name
                ) as basin_name,
                emww.df_id,
                emww.dsa_id,
                emb.dsgr_id,
                emb.dtiel_id,
                emb.dmeas_id
            FROM limited_suspect esm
            INNER JOIN empodat_stations es ON esm.station_id = es.id
            LEFT JOIN most_recent_main em ON em.station_id = esm.station_id
            LEFT JOIN susdat_substances ss ON esm.substance_id = ss.id
            LEFT JOIN empodat_matrix_biota emb ON em.id = emb.id AND em.matrix_id BETWEEN 39 AND 47
            LEFT JOIN empodat_matrix_water_waste emww ON em.id = emww.id AND em.matrix_id BETWEEN 72 AND 74
            LEFT JOIN empodat_matrix_water_surface emws ON em.id = emws.id AND em.matrix_id IN (2,3,4,5,6,7,8)
            LEFT JOIN empodat_matrix_water_ground emwg ON em.id = emwg.id AND em.matrix_id = 1
            WHERE esm.station_id IS NOT NULL AND es.id IS NOT NULL AND em.id IS NOT NULL
        ");
    }
};
