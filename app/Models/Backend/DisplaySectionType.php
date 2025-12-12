<?php

declare(strict_types=1);

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisplaySectionType extends Model
{
    protected $table = 'display_section_types';

    protected $fillable = [
        'code',
        'default_name',
        'description',
        'default_header_bg_class',
        'default_header_text_class',
        'default_row_even_class',
        'default_row_odd_class',
        'default_row_text_class',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all display sections using this type.
     */
    public function displaySections(): HasMany
    {
        return $this->hasMany(DisplaySection::class, 'section_type_id');
    }

    /**
     * Scope to filter only active section types.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<DisplaySectionType>  $query
     * @return \Illuminate\Database\Eloquent\Builder<DisplaySectionType>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
