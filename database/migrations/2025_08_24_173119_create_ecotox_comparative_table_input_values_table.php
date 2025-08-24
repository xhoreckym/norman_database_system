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
        Schema::create('ecotox_comparative_table_input_values', function (Blueprint $table) {
            $table->id();
            $table->integer('val_id')->nullable()->default(null);
            $table->integer('column_id')->nullable()->default(null);
            $table->string('column_name')->nullable()->default(null);
            $table->string('input_value')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_comparative_table_input_values');
    }
};
