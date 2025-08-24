<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Model;

class EcotoxComparativeTableConfig extends Model
{
    protected $table = 'ecotox_comparative_table_configs';

    protected $fillable = [
        'group',
        'header',
        'header_2',
        'column_name',
        'column_id',
        'is_editable',
        'input_type',
        'description',
        'order',
    ];
}
