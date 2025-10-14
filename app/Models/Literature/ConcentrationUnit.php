<?php

namespace App\Models\Literature;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConcentrationUnit extends Model
{
    use HasFactory;

    protected $table = 'list_concentration_units';

    protected $fillable = [
        'name',
    ];
}
