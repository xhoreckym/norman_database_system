<?php

namespace App\Models\Literature;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LifeStage extends Model
{
    use HasFactory;

    protected $table = 'list_life_stages';

    protected $fillable = [
        'name',
    ];
}
