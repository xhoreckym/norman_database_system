<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matrix extends Model
{
    use HasFactory;

    protected $table = 'list_matrices';

    protected $fillable = [
        'title',
        'subtitle',
        'type',
        'name',
        'dct_name',
        'unit',
        'empodat_matrix_link',
    ];
}
