<?php

declare(strict_types=1);

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
        Schema::create('indoor_analytical_method', function (Blueprint $table) {
            $table->id('id_method');

            // LOD/LOQ values
            $table->double('am_lod')->default(0);
            $table->double('am_loq')->default(0);
            $table->string('am_unit', 20)->default('');
            $table->string('am_uncertainty_loq', 255)->default('');

            // Coverage factor reference
            $table->unsignedSmallInteger('dcf_id')->default(0);

            // Sampling method 1
            $table->unsignedSmallInteger('dsm1_id')->default(0);
            $table->string('dsm1_other', 255)->default('');

            // Sampling method 2
            $table->unsignedSmallInteger('dsm2_id')->default(0);
            $table->string('dsm2_other', 255)->default('');

            // Sample preparation method
            $table->unsignedSmallInteger('dpm_id')->default(0);
            $table->string('dpm_other', 255)->default('');

            // Analytical method
            $table->unsignedSmallInteger('dam_id')->default(0);
            $table->string('dam_other', 255)->default('');

            // Standardised method
            $table->unsignedSmallInteger('dsm_id')->default(0);
            $table->string('dsm_other', 255)->default('');

            // Method number/code
            $table->string('am_number', 255)->default('');

            // QA/QC fields (text fields for yes/no/description responses)
            $table->text('am_validated_method')->nullable();
            $table->text('am_corrected_recovery')->nullable();
            $table->text('am_field_blank')->nullable();
            $table->text('am_iso')->nullable();
            $table->text('am_given_analyte')->nullable();
            $table->text('am_laboratory_participate')->nullable();
            $table->text('am_summary_performance')->nullable();
            $table->text('am_control_charts')->nullable();
            $table->text('am_authority')->nullable();
            $table->text('am_remark')->nullable();

            $table->timestamps();

            // Indexes for foreign key lookups
            $table->index('dcf_id');
            $table->index('dsm1_id');
            $table->index('dsm2_id');
            $table->index('dpm_id');
            $table->index('dam_id');
            $table->index('dsm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indoor_analytical_method');
    }
};
