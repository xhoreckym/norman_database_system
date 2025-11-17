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
        Schema::dropIfExists('empodat_matrix_biota');

        Schema::create('empodat_matrix_biota', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            // Name of river / estuary / lake / reservoir / sea
            $table->string('name', 255)->nullable();

            // River basin name
            $table->string('basin_name', 255)->nullable();

            // River-km
            $table->string('km', 255)->nullable();

            // Proxy pressures
            $table->smallInteger('dpr_id')->nullable();

            // Species group
            $table->smallInteger('dsgr_id')->nullable();

            // Species group - other
            $table->string('dsgr_other', 255)->nullable();

            // Species
            $table->text('species')->nullable();

            // Species name (in Latin)
            $table->string('species_name', 255)->nullable();

            // ???????????
            $table->string('species_alive', 255)->nullable();

            // Basis of measurement
            $table->smallInteger('dmeas_id')->nullable();

            // Basis of measurement - other
            $table->string('dmeas_other', 255)->nullable();

            // Tissue element of species monitored (lyophilized)
            $table->smallInteger('dtiel_id')->nullable();

            // Tissue element of species monitored (lyophilized) - other
            $table->string('dtiel_other', 255)->nullable();

            // Biota size [mm]
            $table->string('biota_size', 255)->nullable();

            // Biota length  [mm]
            $table->text('biota_length')->nullable();

            // Biota weight [g]
            $table->string('biota_weight', 255)->nullable();

            // Biota sex
            $table->text('biota_sex')->nullable();

            // Biota age
            $table->text('biota_age')->nullable();

            // Agegroup
            $table->text('agegroup')->nullable();

            // Number of organisms used
            $table->string('number_organisms', 255)->nullable();

            // Water content of tissue [%]
            $table->text('water_content')->nullable();

            // Dry Wet Ratio [weight %]
            $table->string('dry_wet', 255)->nullable();

            // Fat content of tissue  [%]
            $table->string('fat_content', 255)->nullable();

            // Nutrition condition
            $table->integer('nutrition_condition')->nullable();

            // No. of pooled individuals
            $table->text('no_pooled_individuals')->nullable();

            // Standardised protocols for dissection of organs available
            $table->text('standardised_protocols')->nullable();

            // Time of freezing
            $table->text('time_freezing')->nullable();

            // Storage temperature
            $table->text('storage_temperature')->nullable();

            // Packing material of samples
            $table->text('packing_material')->nullable();

            // Geographic range of pooled individuals
            $table->text('geographic_range')->nullable();

            // Was species alive (terrestrial and marine mammals)?
            $table->text('was_species_alive')->nullable();

            // Did a receive medical treatment prior to death?
            $table->text('receive_medical_treatment')->nullable();

            // Was the species euthanised?
            $table->text('was_species_euthanised')->nullable();

            $table->string('cause_death', 255)->nullable();

            $table->string('year_death', 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_biota');
    }
};
