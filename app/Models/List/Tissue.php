<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tissue extends Model
{
    use HasFactory;

    protected $table = 'list_tissues';

    protected $fillable = [
        'name',
    ];
}
