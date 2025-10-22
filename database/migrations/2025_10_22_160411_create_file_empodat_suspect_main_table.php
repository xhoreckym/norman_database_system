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
        if (Schema::hasTable('file_empodat_suspect_main')) {
            return;
        }

        Schema::create('file_empodat_suspect_main', function (Blueprint $table) {
            $table->id();

            // Pivot table linking files to empodat_suspect_main records
            $table->foreignId('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreignId('empodat_suspect_main_id')->references('id')->on('empodat_suspect_main')->onDelete('cascade');

            $table->timestamps();

            // Indexes for faster lookups
            $table->index('file_id');
            $table->index('empodat_suspect_main_id');

            // Unique constraint to prevent duplicate links
            $table->unique(['file_id', 'empodat_suspect_main_id'], 'file_empodat_suspect_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_empodat_suspect_main');
    }
};
