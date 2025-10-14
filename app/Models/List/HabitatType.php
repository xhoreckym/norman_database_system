<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HabitatType extends Model
{
    use HasFactory;

    protected $table = 'list_habitat_types';

    protected $fillable = [
        'name',
    ];
}
