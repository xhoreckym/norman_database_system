<?php

declare(strict_types=1);

namespace App\Models\Indoor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndoorDataDcf extends Model
{
    use HasFactory;

    protected $table = 'indoor_data_dcf';

    protected $fillable = [
        'name',
        'description',
    ];
}
