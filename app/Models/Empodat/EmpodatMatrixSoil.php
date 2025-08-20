<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpodatMatrixSoil extends Model
{
    use HasFactory;

    protected $table = 'empodat_matrix_soil';

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
     * Get the DCT analysis that this matrix belongs to.
     */
    public function dctAnalysis()
    {
        return $this->belongsTo(\App\Models\MariaDB\DCTAnalysis::class, 'dct_analysis_id');
    }
}
