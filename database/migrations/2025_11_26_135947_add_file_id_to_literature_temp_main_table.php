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
            $table->foreignId('file_id')->nullable()->after('id')->constrained('files')->onDelete('restrict');
            $table->index('file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('literature_temp_main', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->dropIndex(['file_id']);
            $table->dropColumn('file_id');
        });
    }
};
