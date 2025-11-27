<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds an index on the ip_max column to optimize confidence level filtering.
     * This index supports range queries for the 5 confidence level intervals.
     */
    public function up(): void
    {
        Schema::table('empodat_suspect_main', function (Blueprint $table) {
            $table->index('ip_max', 'idx_empodat_suspect_main_ip_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_suspect_main', function (Blueprint $table) {
            $table->dropIndex('idx_empodat_suspect_main_ip_max');
        });
    }
};
