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
        Schema::dropIfExists('empodat_matrix_soil');

        Schema::create('empodat_matrix_soil', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            $table->string('basin_name', 255)->nullable();

            // Type of depth sampling: data_depth
            $table->smallInteger('de_id')->nullable();

            // Type of depth sampling - other
            $table->string('de_other', 255)->nullable();

            // Depth [m]
            $table->string('depth_m', 255)->nullable();

            // pH
            $table->string('ph', 255)->nullable();

            // Total organic carbon
            $table->string('carbon', 255)->nullable();

            $table->string('wider_area', 255)->nullable();

            // Dry Wet Ratio
            $table->string('dry_wet', 255)->nullable();

            $table->string('soil_type', 255)->nullable();

            // zastarale - nahradene polozkou dsot_id
            $table->string('soil_texture', 255)->nullable();

            $table->smallInteger('dps_id')->nullable();

            // Grain size distribution: data_grain
            $table->smallInteger('dgra_id')->nullable();

            // Grain size distribution - other
            $table->string('dgra_other', 255)->nullable();

            // Dilution factor in the Use category
            $table->string('dilution_factor', 255)->nullable();

            $table->string('km', 255)->nullable();

            // Soil texture: data_soil_texture
            $table->smallInteger('dsot_id')->nullable();

            // Soil texture - other
            $table->string('dsot_other', 255)->nullable();

            // Concentration normalised for the particle size: data_conc_normal_particle_size
            $table->smallInteger('dcnps_id')->nullable();

            $table->smallInteger('dcat_id')->nullable();

            $table->string('dcat_other', 255)->nullable();

            $table->smallInteger('dtbu_id')->nullable();

            $table->string('dtbu_other', 255)->nullable();

            // Bulk density [g/cm3]
            $table->double('bulk_density')->nullable();

            // Organic carbon content [g/kg]
            $table->double('organic_carbon_content')->nullable();

            // pH (CaCl2)
            $table->double('ph_cacl2')->nullable();

            // pH (H2O)
            $table->double('ph_h2o')->nullable();

            // Proxy pressures / PFAS pressures
            $table->smallInteger('dpr_id')->nullable();

            $table->string('dpr_other', 255)->nullable();

            // Note: Foreign key constraints from legacy system:
            // de_id -> data_depth
            // dps_id -> data_particle_size
            // dgra_id -> data_grain
            // dsot_id -> data_soil_texture
            // dcnps_id -> data_conc_normal_particle_size
            // dcat_id -> data_category
            // dtbu_id -> data_treatment_before_use
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_soil');
    }
};
