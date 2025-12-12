<?php

declare(strict_types=1);

namespace App\Models\Backend;

use App\Models\DatabaseEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisplaySection extends Model
{
    protected $table = 'display_sections';

    protected $fillable = [
        'database_entity_id',
        'section_type_id',
        'code',
        'name',
        'relationship',
        'display_order',
        'header_bg_class',
        'header_text_class',
        'row_even_class',
        'row_odd_class',
        'row_text_class',
        'is_visible',
        'is_collapsible',
        'is_collapsed_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_visible' => 'boolean',
            'is_collapsible' => 'boolean',
            'is_collapsed_default' => 'boolean',
        ];
    }

    /**
     * Get the database entity this section belongs to.
     */
    public function databaseEntity(): BelongsTo
    {
        return $this->belongsTo(DatabaseEntity::class, 'database_entity_id');
    }

    /**
     * Get the section type (template) for this section.
     */
    public function sectionType(): BelongsTo
    {
        return $this->belongsTo(DisplaySectionType::class, 'section_type_id');
    }

    /**
     * Get all columns in this section.
     */
    public function columns(): HasMany
    {
        return $this->hasMany(DisplayColumn::class, 'display_section_id')
            ->orderBy('display_order');
    }

    /**
     * Get visible columns in this section.
     */
    public function visibleColumns(): HasMany
    {
        return $this->hasMany(DisplayColumn::class, 'display_section_id')
            ->where('is_visible', true)
            ->orderBy('display_order');
    }

    /**
     * Get glance columns in this section.
     */
    public function glanceColumns(): HasMany
    {
        return $this->hasMany(DisplayColumn::class, 'display_section_id')
            ->where('is_glance', true)
            ->orderBy('display_order');
    }

    /**
     * Scope to filter only visible sections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<DisplaySection>  $query
     * @return \Illuminate\Database\Eloquent\Builder<DisplaySection>
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope to filter by database entity code.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<DisplaySection>  $query
     * @return \Illuminate\Database\Eloquent\Builder<DisplaySection>
     */
    public function scopeForModule($query, string $moduleCode)
    {
        return $query->whereHas('databaseEntity', function ($q) use ($moduleCode) {
            $q->where('code', $moduleCode);
        });
    }

    /**
     * Get the effective header background class (from section or type default).
     */
    public function getEffectiveHeaderBgClassAttribute(): string
    {
        return $this->header_bg_class
            ?? $this->sectionType?->default_header_bg_class
            ?? 'bg-gray-300';
    }

    /**
     * Get the effective header text class (from section or type default).
     */
    public function getEffectiveHeaderTextClassAttribute(): string
    {
        return $this->header_text_class
            ?? $this->sectionType?->default_header_text_class
            ?? 'text-gray-900';
    }

    /**
     * Get the effective row even class (from section or type default).
     */
    public function getEffectiveRowEvenClassAttribute(): string
    {
        return $this->row_even_class
            ?? $this->sectionType?->default_row_even_class
            ?? 'bg-slate-100';
    }

    /**
     * Get the effective row odd class (from section or type default).
     */
    public function getEffectiveRowOddClassAttribute(): string
    {
        return $this->row_odd_class
            ?? $this->sectionType?->default_row_odd_class
            ?? 'bg-slate-200';
    }

    /**
     * Get the effective row text class (from section or type default).
     */
    public function getEffectiveRowTextClassAttribute(): string
    {
        return $this->row_text_class
            ?? $this->sectionType?->default_row_text_class
            ?? 'text-gray-900';
    }

    /**
     * Get the effective name (from section or type default).
     */
    public function getEffectiveNameAttribute(): string
    {
        return $this->name
            ?? $this->sectionType?->default_name
            ?? ucfirst(str_replace('_', ' ', $this->code));
    }
}
