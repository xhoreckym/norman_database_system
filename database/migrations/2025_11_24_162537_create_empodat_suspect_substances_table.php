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
        if (Schema::hasTable('empodat_suspect_substances')) {
            return;
        }

        Schema::create('empodat_suspect_substances', function (Blueprint $table) {
            $table->id();
            $table->string('norman_id')->nullable()->default(null);
            $table->text('name')->nullable()->default(null);
            $table->foreignId('file_id')->nullable()->default(null)->references('id')->on('files');
            // $table->timestamps();

            // Add indexes for better query performance
            $table->index('norman_id');
            $table->index('file_id');
            // $table->index(['norman_id', 'file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_suspect_substances');
    }
};
