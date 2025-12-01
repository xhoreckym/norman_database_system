<?php

declare(strict_types=1);

namespace App\Models\Passive;

use Illuminate\Database\Eloquent\Model;

class PassiveDataSource extends Model
{
    protected $table = 'passive_data_source';

    protected $fillable = [
        'org_name',
        'org_city',
        'org_country',
        'org_lab1_name',
        'org_lab1_city',
        'org_lab1_country',
        'org_lab2_name',
        'org_lab2_city',
        'org_lab2_country',
        'org_family_name',
        'org_first_name',
        'org_email',
    ];
}
