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
        Schema::create('passive_analytical_method', function (Blueprint $table) {
            $table->id();
            $table->string('am_unit', 50)->nullable();
            $table->string('am_detection_limit', 50)->nullable();
            $table->string('am_quantification_limit', 50)->nullable();
            $table->string('dpm_id', 10)->nullable();
            $table->text('dpm_other')->nullable();
            $table->string('dam_id', 10)->nullable();
            $table->string('dam_other', 255)->nullable();
            $table->string('dsm_id', 10)->nullable();
            $table->string('dsm_number', 255)->nullable();
            $table->string('dsm_other', 255)->nullable();
            $table->string('dp_id', 10)->nullable();
            $table->string('am_extraction_recovery_correction', 50)->nullable();
            $table->string('am_field_blank_check', 50)->nullable();
            $table->string('am_lab_iso17025', 50)->nullable();
            $table->string('am_lab_accredited', 50)->nullable();
            $table->string('am_interlab_studies', 255)->nullable();
            $table->string('am_interlab_summary', 255)->nullable();
            $table->string('am_control_charts', 255)->nullable();
            $table->string('am_authority_control', 255)->nullable();
            $table->text('am_remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passive_analytical_method');
    }
};
