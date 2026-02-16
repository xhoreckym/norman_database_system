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
        Schema::table('literature_temp_main', function (Blueprint $table) {
            $table->text('concentration_unit_raw')->nullable()->after('concentration_units_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('literature_temp_main', function (Blueprint $table) {
            $table->dropColumn('concentration_unit_raw');
        });
    }
};
