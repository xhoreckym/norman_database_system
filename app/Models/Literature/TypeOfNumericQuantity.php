<?php

namespace App\Models\Literature;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfNumericQuantity extends Model
{
    use HasFactory;

    protected $table = 'list_type_of_numeric_quantities';

    protected $fillable = [
        'name',
    ];
}
