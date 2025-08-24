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
        Schema::create('ecotox_comparative_table_configs', function (Blueprint $table) {
            $table->id();
            $table->string('group')->nullable();
            $table->string('header')->nullable();
            $table->string('header_2')->nullable();
            $table->string('column_name')->nullable();
            $table->integer('column_id')->nullable();
            $table->boolean('is_editable')->nullable();
            $table->string('input_type')->nullable();
            $table->string('description')->nullable();
            $table->integer('order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecotox_comparative_table_configs');
    }
};
