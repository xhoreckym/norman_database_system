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
        // Drop the table if it exists
        Schema::dropIfExists('empodat_matrix_water_ground');

        Schema::create('empodat_matrix_water_ground', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            // Fraction - data_fraction
            $table->smallInteger('df_id')->nullable();

            $table->string('df_other', 255)->nullable();

            $table->string('name', 255)->nullable();

            $table->string('basin_name', 255)->nullable();

            // Proxy pressures - data_pressures
            $table->smallInteger('dpr_id')->nullable();

            $table->smallInteger('de_id')->nullable();

            $table->string('de_other', 255)->nullable();

            $table->string('depth_m', 255)->nullable();

            $table->string('carbon', 255)->nullable();

            $table->string('ph', 255)->nullable();

            // Temperature [°C]
            $table->string('temperature', 10)->nullable();

            // SPM conc.  [mg/l]
            $table->string('spm_conc', 10)->nullable();

            // Conductivity [S/m]
            $table->string('conductivity', 10)->nullable();

            // DOC [mg/l]
            $table->string('doc', 10)->nullable();

            // Hardness [mg/l] CaCO3
            $table->string('hardness', 10)->nullable();

            // O2 [mg/l]
            $table->string('o2_m', 10)->nullable();

            // O2 [%]
            $table->string('o2_p', 10)->nullable();

            // BOD5 [mg/l]
            $table->string('bod5', 10)->nullable();

            // Use category - data_category
            $table->smallInteger('dcat_id')->nullable();

            // Use category / Other
            $table->string('dcat_other', 255)->nullable();

            // H2S [mg/l]
            $table->string('h2s', 10)->nullable();

            // P (PO4) [mg/l]
            $table->string('p_po4', 10)->nullable();

            // N (NO2) [mg/l]
            $table->string('n_no2', 10)->nullable();

            // TSS [mg/l]
            $table->string('tss', 10)->nullable();

            // P total [mg/l]
            $table->string('p_total', 10)->nullable();

            // N (NO3) [mg/l]
            $table->string('n_no3', 10)->nullable();

            // N total [mg/l]
            $table->string('n_total', 10)->nullable();

            // Note: Foreign key constraints from legacy system:
            // df_id -> data_fraction
            // dpr_id -> data_pressures
            // de_id -> data_depth
            // dcat_id -> data_category
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_water_ground');
    }
};
