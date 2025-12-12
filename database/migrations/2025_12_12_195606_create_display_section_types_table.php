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
        Schema::create('display_section_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 255)->unique();
            $table->string('default_name', 255)->default('');
            $table->text('description')->nullable()->default(null);
            $table->string('default_header_bg_class', 100)->default('bg-gray-300');
            $table->string('default_header_text_class', 100)->default('text-gray-900');
            $table->string('default_row_even_class', 100)->default('bg-slate-100');
            $table->string('default_row_odd_class', 100)->default('bg-slate-200');
            $table->string('default_row_text_class', 100)->default('text-gray-900');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('display_section_types');
    }
};
