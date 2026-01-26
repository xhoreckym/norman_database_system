<?php

declare(strict_types=1);

namespace App\Models\Indoor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndoorDataDpm extends Model
{
    use HasFactory;

    protected $table = 'indoor_data_dpm';

    protected $fillable = [
        'name',
        'description',
    ];
}
