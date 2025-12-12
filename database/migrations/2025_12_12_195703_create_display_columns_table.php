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
        Schema::create('display_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('display_section_id')
                ->constrained('display_sections')
                ->onDelete('cascade');
            $table->string('column_name', 255);
            $table->string('display_label', 500)->nullable()->default(null);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_glance')->default(false);
            $table->integer('display_order')->default(0);
            $table->string('format_type', 50)->default('text');
            $table->jsonb('format_options')->nullable()->default(null);
            $table->string('fallback_column', 255)->nullable()->default(null);
            $table->string('css_class', 255)->nullable()->default(null);
            $table->string('link_route', 255)->nullable()->default(null);
            $table->string('link_param', 255)->nullable()->default(null);
            $table->text('tooltip')->nullable()->default(null);
            $table->timestamps();

            $table->unique(['display_section_id', 'column_name']);
            $table->index('display_section_id');
            $table->index('is_visible');
            $table->index('is_glance');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('display_columns');
    }
};
