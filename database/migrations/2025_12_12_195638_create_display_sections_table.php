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
        Schema::create('display_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_entity_id')
                ->constrained('database_entities')
                ->onDelete('cascade');
            $table->foreignId('section_type_id')
                ->nullable()
                ->default(null)
                ->constrained('display_section_types')
                ->onDelete('set null');
            $table->string('code', 100);
            $table->string('name', 255)->nullable()->default(null);
            $table->string('relationship', 255)->nullable()->default(null);
            $table->integer('display_order')->default(0);
            $table->string('header_bg_class', 100)->nullable()->default(null);
            $table->string('header_text_class', 100)->nullable()->default(null);
            $table->string('row_even_class', 100)->nullable()->default(null);
            $table->string('row_odd_class', 100)->nullable()->default(null);
            $table->string('row_text_class', 100)->nullable()->default(null);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_collapsible')->default(false);
            $table->boolean('is_collapsed_default')->default(false);
            $table->timestamps();

            $table->unique(['database_entity_id', 'code']);
            $table->index('database_entity_id');
            $table->index('section_type_id');
            $table->index('display_order');
            $table->index('is_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('display_sections');
    }
};
