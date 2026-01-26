<?php

declare(strict_types=1);

namespace App\Models\Indoor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndoorDataDsm extends Model
{
    use HasFactory;

    protected $table = 'indoor_data_dsm';

    protected $fillable = [
        'name',
        'description',
    ];
}
