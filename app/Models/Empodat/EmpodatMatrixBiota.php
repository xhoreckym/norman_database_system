<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpodatMatrixBiota extends Model
{
    use HasFactory;

    protected $table = 'empodat_matrix_biota';

    protected $fillable = [
        'dct_analysis_id',
        'code',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'dct_analysis_id' => 'integer',
    ];

    /**
     * Get the meta_data attribute
     */
    public function getMetaDataAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    /**
     * Set the meta_data attribute
     */
    public function setMetaDataAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['meta_data'] = json_encode($value);
        } else {
            $this->attributes['meta_data'] = $value;
        }
    }

    /**
     * Get the DCT analysis that this matrix belongs to.
     */
    public function dctAnalysis()
    {
        return $this->belongsTo(\App\Models\MariaDB\DCTAnalysis::class, 'dct_analysis_id');
    }
}
