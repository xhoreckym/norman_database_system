<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConcentrationIndicator extends Model
{
    use HasFactory;

    protected $table = 'list_concentration_indicators';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        // No special casting needed for this model
    ];

    /**
     * Get the concentration indicator name in a formatted way.
     */
    public function getFormattedNameAttribute()
    {
        return ucfirst(trim($this->name));
    }

    /**
     * Scope to filter by name containing specific text.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Scope to filter by exact name match.
     */
    public function scopeByExactName($query, $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Scope to order by name alphabetically.
     */
    public function scopeOrderedByName($query)
    {
        return $query->orderBy('name', 'asc');
    }

    /**
     * Get all concentration indicators ordered by name.
     */
    public static function getAllOrdered()
    {
        return static::orderedByName()->get();
    }

    /**
     * Get concentration indicators as a key-value array for select dropdowns.
     */
    public static function getForSelect()
    {
        return static::orderedByName()->pluck('name', 'id');
    }

    /**
     * Search concentration indicators by name.
     */
    public static function searchByName($searchTerm)
    {
        return static::byName($searchTerm)->orderedByName()->get();
    }
}
