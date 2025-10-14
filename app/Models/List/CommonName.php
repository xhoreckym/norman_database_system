<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonName extends Model
{
    use HasFactory;

    protected $table = 'list_common_names';

    protected $fillable = [
        'name',
    ];
}
