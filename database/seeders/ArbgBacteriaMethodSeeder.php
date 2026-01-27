<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArbgBacteriaMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'method_id' => 1,
                'lod' => 25,
                'lod_unit' => 'CFU/ml',
                'loq' => 75,
                'loq_unit' => 'CFU/ml',
                'bacteria_isolation_method_id' => 3, // Under selective pressure
                'phenotype_determination_method_id' => 2, // Microdilution
                'interpretation_criteria_id' => 1, // CLSI (assuming id 1)
            ],
        ];

        foreach ($methods as $method) {
            DB::table('arbg_bacteria_method')->updateOrInsert(
                ['method_id' => $method['method_id']],
                array_merge($method, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
