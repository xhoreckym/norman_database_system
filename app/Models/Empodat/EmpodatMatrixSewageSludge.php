<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpodatMatrixSewageSludge extends Model
{
    use HasFactory;

    protected $table = 'empodat_matrix_sewage_sludge';

    protected $fillable = [
        'code',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];
}
