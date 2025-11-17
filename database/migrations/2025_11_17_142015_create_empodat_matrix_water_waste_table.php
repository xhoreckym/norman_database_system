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
        Schema::dropIfExists('empodat_matrix_water_waste');

        Schema::create('empodat_matrix_water_waste', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            $table->smallInteger('df_id')->nullable();

            $table->string('basin_name', 255)->nullable();

            $table->string('name', 255)->nullable();

            // Fraction - data_fraction
            $table->smallInteger('dpr_id')->nullable();

            $table->string('ph', 255)->nullable();

            $table->string('temperature', 255)->nullable();

            $table->string('carbon', 255)->nullable();

            $table->string('hardness', 255)->nullable();

            $table->string('type_industry', 255)->nullable();

            $table->string('capacity', 255)->nullable();

            $table->string('flow', 255)->nullable();

            // Effluent/Influent (data_effluent_influent)
            $table->smallInteger('effluent_influent_id')->nullable();

            $table->string('effluent_influent_other', 50)->nullable();

            // Type of wastewater (data_type_waste)
            $table->smallInteger('dtw_id')->nullable();

            $table->smallInteger('dtp_id')->nullable();

            $table->string('dtp_other', 255)->nullable();

            // Advanced treatment steps (data_tertiary_treatment)
            $table->smallInteger('dtt_id')->nullable();

            $table->string('dtt_other', 255)->nullable();

            $table->string('dry_matter', 255)->nullable();

            $table->string('srt', 255)->nullable();

            $table->string('reactor', 255)->nullable();

            // Sampling method
            $table->smallInteger('dsa_id')->nullable();

            $table->string('dsa_other', 255)->nullable();

            $table->smallInteger('de_id')->nullable();

            $table->string('de_other', 255)->nullable();

            $table->string('depth_m', 255)->nullable();

            // Sludge retention time / [day/s] / Number
            $table->string('sludge_retention_time', 20)->nullable();

            $table->smallInteger('dcat_id')->nullable();

            $table->string('dcat_other', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_water_waste');
    }
};
