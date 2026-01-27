<?php

declare(strict_types=1);

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Model;

class DataTargetedAnalysis extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'arbg_data_targeted_analysis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'ordering',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'ordering' => 'integer',
    ];
}
