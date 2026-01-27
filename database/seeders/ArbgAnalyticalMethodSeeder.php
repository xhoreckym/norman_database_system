<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArbgAnalyticalMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = database_path('seeders/seeds/arbg_tables/arg_analytical_method.csv');

        if (! file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");

            return;
        }

        $handle = fopen($csvPath, 'r');
        if (! $handle) {
            $this->command->error("Could not open CSV file: {$csvPath}");

            return;
        }

        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            $methodId = (int) $row[0];

            $data = [
                'method_id' => $methodId,
                'type_of_sample_id' => ! empty($row[1]) ? (int) $row[1] : 0,
                'type_of_sample_other' => ! empty($row[2]) ? $row[2] : null,
                'volume_of_sample_used_for_dna_extraction' => ! empty($row[3]) ? $row[3] : null,
                'method_used_for_dna_extraction' => ! empty($row[4]) ? $row[4] : null,
                'targeted_analysis_id' => ! empty($row[5]) ? (int) $row[5] : 0,
                'targeted_analysis_other' => ! empty($row[6]) ? $row[6] : null,
                'non_targeted_analysis_id' => ! empty($row[7]) ? (int) $row[7] : 0,
                'non_targeted_analysis_other' => ! empty($row[8]) ? $row[8] : null,
                'analysis_of_pooled_dna_extracts' => ! empty($row[9]) ? $row[9] : null,
                'analysis_of_pooled_dna_extracts_specify' => ! empty($row[10]) ? $row[10] : null,
                'dna' => ! empty($row[11]) ? $row[11] : null,
                'limit_of_detection' => ! empty($row[12]) ? $row[12] : null,
                'limit_of_quantification' => ! empty($row[13]) ? $row[13] : null,
                'uncertainty_of_the_quantification' => ! empty($row[14]) ? $row[14] : null,
                'efficiency' => ! empty($row[15]) ? $row[15] : null,
                'sequencing_read_depth' => ! empty($row[16]) ? $row[16] : null,
                'analytical_method_id' => ! empty($row[17]) ? (int) $row[17] : 0,
                'analytical_method_other' => ! empty($row[18]) ? $row[18] : null,
                'remarks' => ! empty($row[19]) ? $row[19] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('arbg_analytical_method')->updateOrInsert(
                ['method_id' => $methodId],
                $data
            );

            $count++;
        }

        fclose($handle);

        $this->command->info("Seeded {$count} analytical method records.");
    }
}
