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
        Schema::dropIfExists('empodat_matrix_water_surface');

        Schema::create('empodat_matrix_water_surface', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            $table->smallInteger('df_id')->nullable();

            $table->string('df_other', 255)->nullable();

            $table->string('name', 255)->nullable();

            $table->string('basin_name', 255)->nullable();

            $table->string('km', 255)->nullable();

            $table->smallInteger('dpr_id')->nullable();

            $table->string('dpr_other', 255)->nullable();

            $table->smallInteger('de_id')->nullable();

            $table->string('de_other', 255)->nullable();

            $table->string('depth_m', 255)->nullable();

            $table->string('surface', 255)->nullable();

            $table->string('salinity_min', 255)->nullable();

            $table->string('salinity_mean', 255)->nullable();

            $table->string('salinity_max', 255)->nullable();

            $table->string('spm', 255)->nullable();

            $table->string('ph', 255)->nullable();

            $table->string('temperature', 255)->nullable();

            // Conductivity
            $table->text('conductivity')->nullable();

            $table->string('doc', 10)->nullable();

            $table->string('carbon', 255)->nullable();

            $table->string('hardness', 255)->nullable();

            $table->text('horizon')->nullable();

            $table->text('doc_mg_cl')->nullable();

            // O2 [mg/l]
            $table->text('o2_m')->nullable();

            // O2 [%]
            $table->string('o2_p', 10)->nullable();

            $table->smallInteger('dcat_id')->nullable();

            $table->string('dcat_other', 255)->nullable();

            $table->smallInteger('dtbu_id')->nullable();

            // P total [mg/l]
            $table->string('p_total', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_water_surface');
    }
};
