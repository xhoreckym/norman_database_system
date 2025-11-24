<?php

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectConnect2SedimentsXlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * This seeder fills the station_id, count, and ids columns in
     * empodat_suspect_xlsx_stations_mapping by matching xlsx_name
     * with empodat_stations.short_sample_code for CONNECT 2 SEDIMENTS data
     * Handles two patterns: CONnECTII and DnieperII
     */
    public function run(): void
    {
        $this->command->info('Filling empodat_suspect_xlsx_stations_mapping for CONNECT 2 SEDIMENTS data...');

        // First handle CONnECTII pattern
        $updateQuery1 = "
UPDATE empodat_suspect_xlsx_stations_mapping m
SET
    station_id = subquery.first_station_id,
    count = subquery.station_count,
    ids = subquery.all_station_ids,
    updated_at = NOW()
FROM (
    SELECT
        m.id as mapping_id,
        COUNT(s.id) as station_count,
        MIN(s.id) as first_station_id,
        STRING_AGG(s.id::text, ', ' ORDER BY s.id) as all_station_ids
    FROM
        empodat_suspect_xlsx_stations_mapping m
    LEFT JOIN
        empodat_stations s ON
        -- Match CONnECTII pattern (case-insensitive)
        LOWER(s.short_sample_code) = LOWER('CONNECTII ' || REGEXP_REPLACE(
            SUBSTRING(LOWER(m.xlsx_name) FROM 'conn?ectii ([0-9]+)'),
            '^0+',
            ''
        ))
        AND (s.is_deprecated IS NULL OR s.is_deprecated = false)
    WHERE
        LOWER(m.xlsx_name) LIKE 'conn%ectii%'
    GROUP BY
        m.id
) AS subquery
WHERE m.id = subquery.mapping_id;
";

        $affectedRows1 = DB::update($updateQuery1);
        $this->command->info("Successfully updated {$affectedRows1} CONnECTII rows.");

        // Then handle DnieperII pattern
        $updateQuery2 = "
UPDATE empodat_suspect_xlsx_stations_mapping m
SET
    station_id = subquery.first_station_id,
    count = subquery.station_count,
    ids = subquery.all_station_ids,
    updated_at = NOW()
FROM (
    SELECT
        m.id as mapping_id,
        COUNT(s.id) as station_count,
        MIN(s.id) as first_station_id,
        STRING_AGG(s.id::text, ', ' ORDER BY s.id) as all_station_ids
    FROM
        empodat_suspect_xlsx_stations_mapping m
    LEFT JOIN
        empodat_stations s ON
        -- Match DnieperII-XX pattern (case-insensitive)
        LOWER(s.short_sample_code) = LOWER('DnieperII-' || REGEXP_REPLACE(
            SUBSTRING(LOWER(m.xlsx_name) FROM 'dnieperii-([0-9]+)'),
            '^0+',
            ''
        ))
        AND (s.is_deprecated IS NULL OR s.is_deprecated = false)
    WHERE
        LOWER(m.xlsx_name) LIKE 'sediment from%dnieperii%'
    GROUP BY
        m.id
) AS subquery
WHERE m.id = subquery.mapping_id;
";

        $affectedRows2 = DB::update($updateQuery2);
        $this->command->info("Successfully updated {$affectedRows2} DnieperII rows.");

        $this->command->info("Total updated: " . ($affectedRows1 + $affectedRows2) . " rows.");
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2SedimentsXlsxStationsMappingFillSeeder
