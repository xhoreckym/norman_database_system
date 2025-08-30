<?php

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Model;

class DataCountry extends Model
{
    protected $table = 'arbg_data_country';
    
    protected $fillable = [
        'abbreviation',
        'name',
        'description',
        'is_active',
        'ordering',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'ordering' => 'integer',
    ];
}
