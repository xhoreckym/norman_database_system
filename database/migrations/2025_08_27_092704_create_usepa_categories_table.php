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
        Schema::create('susdat_usepa_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sus_id')->comment('Legacy table identifier');
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances')->onUpdate('cascade')->onDelete('restrict');
            $table->text('category_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('susdat_usepa_categories');
    }
};
