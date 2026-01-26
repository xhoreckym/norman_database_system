<?php

declare(strict_types=1);

namespace App\Models\Indoor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndoorDataDam extends Model
{
    use HasFactory;

    protected $table = 'indoor_data_dam';

    protected $fillable = [
        'name',
        'description',
    ];
}
