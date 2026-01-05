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
        Schema::create('empodat_suspect_susdat_code_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('old_legacy_norman_id')->nullable();
            $table->string('old_code')->index();
            $table->string('new_code')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('old_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_suspect_susdat_code_mappings');
    }
};
