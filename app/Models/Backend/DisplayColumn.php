<?php

declare(strict_types=1);

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DisplayColumn extends Model
{
    protected $table = 'display_columns';

    protected $fillable = [
        'display_section_id',
        'column_name',
        'display_label',
        'is_visible',
        'is_glance',
        'display_order',
        'format_type',
        'format_options',
        'fallback_column',
        'css_class',
        'link_route',
        'link_param',
        'tooltip',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_glance' => 'boolean',
            'display_order' => 'integer',
            'format_options' => 'array',
        ];
    }

    /**
     * Get the section this column belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(DisplaySection::class, 'display_section_id');
    }

    /**
     * Scope to filter only visible columns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<DisplayColumn>  $query
     * @return \Illuminate\Database\Eloquent\Builder<DisplayColumn>
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope to filter glance columns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<DisplayColumn>  $query
     * @return \Illuminate\Database\Eloquent\Builder<DisplayColumn>
     */
    public function scopeGlance($query)
    {
        return $query->where('is_glance', true);
    }

    /**
     * Get the effective display label (from column or auto-generated).
     */
    public function getEffectiveLabelAttribute(): string
    {
        if ($this->display_label !== null && $this->display_label !== '') {
            return $this->display_label;
        }

        return $this->generateLabelFromColumnName($this->column_name);
    }

    /**
     * Generate a human-readable label from a snake_case column name.
     */
    protected function generateLabelFromColumnName(string $columnName): string
    {
        $prefixesToStrip = [
            'am_',
            'ds_',
            'org_',
            'dpm_',
            'dam_',
            'dsm_',
            'dp_',
            'dpc_',
            'dpr_',
            'dts_',
            'dtm_',
            'dic_',
            'p_a_',
        ];

        $label = $columnName;

        foreach ($prefixesToStrip as $prefix) {
            if (Str::startsWith($label, $prefix)) {
                $label = Str::substr($label, Str::length($prefix));
                break;
            }
        }

        $acronyms = [
            'lod' => 'LoD',
            'loq' => 'LoQ',
            'iso' => 'ISO',
            'ec' => 'EC',
            'ph' => 'pH',
            'spm' => 'SPM',
            'doc' => 'DOC',
            'bod5' => 'BOD5',
            'tss' => 'TSS',
            'cas' => 'CAS',
            'id' => 'ID',
            'url' => 'URL',
            'h2s' => 'H₂S',
            'o2' => 'O₂',
            'po4' => 'PO₄',
            'no2' => 'NO₂',
            'no3' => 'NO₃',
        ];

        $label = str_replace('_', ' ', $label);
        $label = ucfirst($label);

        foreach ($acronyms as $lower => $proper) {
            $label = preg_replace('/\b'.preg_quote($lower, '/').'\b/i', $proper, $label);
        }

        return $label;
    }

    /**
     * Format a value according to this column's format_type and format_options.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public function formatValue($value)
    {
        if ($value === null) {
            return null;
        }

        $options = $this->format_options ?? [];

        return match ($this->format_type) {
            'number' => $this->formatNumber($value, $options),
            'date' => $this->formatDate($value, $options),
            'datetime' => $this->formatDateTime($value, $options),
            'boolean' => $this->formatBoolean($value, $options),
            'json' => $this->formatJson($value),
            default => $value,
        };
    }

    /**
     * Format a number value.
     *
     * @param  mixed  $value
     * @param  array<string, mixed>  $options
     */
    protected function formatNumber($value, array $options): string
    {
        $decimals = $options['decimals'] ?? 2;
        $decimalSeparator = $options['decimal_separator'] ?? '.';
        $thousandsSeparator = $options['thousands_separator'] ?? ' ';

        return number_format((float) $value, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format a date value.
     *
     * @param  mixed  $value
     * @param  array<string, mixed>  $options
     */
    protected function formatDate($value, array $options): string
    {
        $format = $options['format'] ?? 'd.m.Y';

        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        return date($format, strtotime((string) $value));
    }

    /**
     * Format a datetime value.
     *
     * @param  mixed  $value
     * @param  array<string, mixed>  $options
     */
    protected function formatDateTime($value, array $options): string
    {
        $format = $options['format'] ?? 'd.m.Y H:i:s';

        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        return date($format, strtotime((string) $value));
    }

    /**
     * Format a boolean value.
     *
     * @param  mixed  $value
     * @param  array<string, mixed>  $options
     */
    protected function formatBoolean($value, array $options): string
    {
        $trueLabel = $options['true_label'] ?? 'Yes';
        $falseLabel = $options['false_label'] ?? 'No';

        return $value ? $trueLabel : $falseLabel;
    }

    /**
     * Format a JSON value.
     *
     * @param  mixed  $value
     */
    protected function formatJson($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
