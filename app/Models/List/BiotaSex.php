<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiotaSex extends Model
{
    use HasFactory;

    protected $table = 'list_biota_sexs';

    protected $fillable = [
        'name',
    ];
}
