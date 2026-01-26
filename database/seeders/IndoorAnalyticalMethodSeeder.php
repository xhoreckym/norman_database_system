<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IndoorAnalyticalMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Indoor Analytical Method seeding...');

        $targetTable = 'indoor_analytical_method';
        $now = Carbon::now();
        $startTime = microtime(true);
        $path = base_path('database/seeders/seeds/indoor_tables/indoor_analytical_method.csv');

        if (! file_exists($path)) {
            $this->command->error("File not found: {$path}");

            return;
        }

        // Clear existing data
        DB::table($targetTable)->delete();

        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();

        // Open file and process
        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->command->error("Cannot open file: {$path}");

            return;
        }

        $chunkSize = 500;
        $records = [];
        $totalCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                continue;
            }

            $records[] = [
                'id_method' => $this->parseInteger($row[0] ?? null),
                'am_lod' => $this->parseDouble($row[1] ?? null),
                'am_loq' => $this->parseDouble($row[2] ?? null),
                'am_unit' => $row[3] ?? '',
                'am_uncertainty_loq' => $row[4] ?? '',
                'dcf_id' => $this->parseInteger($row[5] ?? null),
                'dsm1_id' => $this->parseInteger($row[6] ?? null),
                'dsm1_other' => $row[7] ?? '',
                'dsm2_id' => $this->parseInteger($row[8] ?? null),
                'dsm2_other' => $row[9] ?? '',
                'dpm_id' => $this->parseInteger($row[10] ?? null),
                'dpm_other' => $row[11] ?? '',
                'dam_id' => $this->parseInteger($row[12] ?? null),
                'dam_other' => $row[13] ?? '',
                'dsm_id' => $this->parseInteger($row[14] ?? null),
                'dsm_other' => $row[15] ?? '',
                'am_number' => $row[16] ?? '',
                'am_validated_method' => $this->nullIfEmpty($row[17] ?? null),
                'am_corrected_recovery' => $this->nullIfEmpty($row[18] ?? null),
                'am_field_blank' => $this->nullIfEmpty($row[19] ?? null),
                'am_iso' => $this->nullIfEmpty($row[20] ?? null),
                'am_given_analyte' => $this->nullIfEmpty($row[21] ?? null),
                'am_laboratory_participate' => $this->nullIfEmpty($row[22] ?? null),
                'am_summary_performance' => $this->nullIfEmpty($row[23] ?? null),
                'am_control_charts' => $this->nullIfEmpty($row[24] ?? null),
                'am_authority' => $this->nullIfEmpty($row[25] ?? null),
                'am_remark' => $this->nullIfEmpty($row[26] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Insert in chunks
            if (count($records) >= $chunkSize) {
                DB::table($targetTable)->insert($records);
                $totalCount += count($records);
                $this->command->info("Inserted {$totalCount} records...");
                $records = [];
            }
        }

        // Insert remaining records
        if (! empty($records)) {
            DB::table($targetTable)->insert($records);
            $totalCount += count($records);
        }

        fclose($handle);

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->command->info("Indoor Analytical Method seeding completed! {$totalCount} records in {$elapsed}s");
    }

    /**
     * Parse integer value, defaulting to 0 for empty/null
     */
    private function parseInteger(?string $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    /**
     * Parse double value, defaulting to 0.0 for empty/null
     */
    private function parseDouble(?string $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    /**
     * Return null if value is empty
     */
    private function nullIfEmpty(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
// php artisan db:seed --class=IndoorAnalyticalMethodSeeder
