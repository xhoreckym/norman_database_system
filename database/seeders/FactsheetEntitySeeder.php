<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Factsheet\FactsheetEntity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FactsheetEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $entities = [
            [
                'name' => 'Chemical identity',
                'sort_order' => 1,
                'data' => json_encode(['method_of_presentation' => 'database_table', 'model' => 'App\Models\Susdat\Substance', 'fields' => ['name', 'cas_number', 'stdinchikey', 'prefixed_code']]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Major uses',
                'sort_order' => 2,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Properties',
                'sort_order' => 3,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Environmental occurrence (all data)',
                'sort_order' => 4,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Environmental occurrence (detailed information)',
                'sort_order' => 5,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '(Eco)toxicity',
                'sort_order' => 6,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'PBT/vPvB & PMT/vPvM (NORMAN)',
                'sort_order' => 7,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'CMR & ED (NORMAN)',
                'sort_order' => 8,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Potential risk of exceedance of lowest PNEC',
                'sort_order' => 9,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Conclusions and recommendations',
                'sort_order' => 10,
                'data' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Bibliography, sources and supportive information',
                'sort_order' => 11,
                'data' => json_encode(['method_of_presentation' => 'text', 'text' => 'Dulio V. and Von der Ohe P. (2013) NORMAN Prioritisation framework for emerging substances. NORMAN Association, Verneuil en Halatte, France, 70 pages.']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        FactsheetEntity::insert($entities);
    }
}
// php artisan db:seed --class=FactsheetEntitySeeder