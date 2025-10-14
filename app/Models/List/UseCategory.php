<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UseCategory extends Model
{
    use HasFactory;

    protected $table = 'list_use_categories';

    protected $fillable = [
        'name',
    ];
}
