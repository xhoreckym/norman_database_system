<?php

namespace App\Models\Literature;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Species extends Model
{
    use HasFactory;

    protected $table = 'list_species';

    protected $fillable = [
        'name',
        'name_latin',
        'kingdom',
        'phylum',
        'order',
        'class',
        'genus',
    ];
}
