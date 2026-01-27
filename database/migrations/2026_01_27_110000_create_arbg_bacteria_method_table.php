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
        Schema::create('arbg_bacteria_method', function (Blueprint $table) {
            $table->unsignedInteger('method_id')->primary();
            $table->decimal('lod', 12, 4)->nullable()->comment('Limit of Detection');
            $table->string('lod_unit', 50)->nullable()->default('CFU/ml')->comment('LoD Unit');
            $table->decimal('loq', 12, 4)->nullable()->comment('Limit of Quantification');
            $table->string('loq_unit', 50)->nullable()->default('CFU/ml')->comment('LoQ Unit');
            $table->unsignedTinyInteger('bacteria_isolation_method_id')->nullable()->comment('Bacteria isolation method');
            $table->unsignedTinyInteger('phenotype_determination_method_id')->nullable()->comment('Phenotype determination method');
            $table->unsignedTinyInteger('interpretation_criteria_id')->nullable()->comment('Interpretation criteria');
            $table->timestamps();

            $table->index('bacteria_isolation_method_id');
            $table->index('phenotype_determination_method_id');
            $table->index('interpretation_criteria_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbg_bacteria_method');
    }
};
