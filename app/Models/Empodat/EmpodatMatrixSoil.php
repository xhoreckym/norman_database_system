<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpodatMatrixSoil extends Model
{
    use HasFactory;

    protected $table = 'empodat_matrix_soil';

    protected $fillable = [
        'code',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];
}
