<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'empodat_matrix_air',
            'empodat_matrix_biota',
            'empodat_matrix_sediments',
            'empodat_matrix_sewage_sludge',
            'empodat_matrix_soil',
            'empodat_matrix_suspended_matter',
            'empodat_matrix_water_surface',
            'empodat_matrix_water_ground',
            'empodat_matrix_water_waste',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->primary('id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'empodat_matrix_air',
            'empodat_matrix_biota',
            'empodat_matrix_sediments',
            'empodat_matrix_sewage_sludge',
            'empodat_matrix_soil',
            'empodat_matrix_suspended_matter',
            'empodat_matrix_water_surface',
            'empodat_matrix_water_ground',
            'empodat_matrix_water_waste',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropPrimary($tableName . '_pkey');
                });
            }
        }
    }
};
