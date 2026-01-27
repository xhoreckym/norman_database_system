<?php

declare(strict_types=1);

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Model;

class BacteriaMethod extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'arbg_bacteria_method';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'method_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'method_id',
        'lod',
        'lod_unit',
        'loq',
        'loq_unit',
        'bacteria_isolation_method_id',
        'phenotype_determination_method_id',
        'interpretation_criteria_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lod' => 'decimal:4',
        'loq' => 'decimal:4',
        'bacteria_isolation_method_id' => 'integer',
        'phenotype_determination_method_id' => 'integer',
        'interpretation_criteria_id' => 'integer',
    ];

    /**
     * Get the bacteria isolation method record.
     */
    public function bacteriaIsolationMethod()
    {
        return $this->belongsTo(DataBacteriaIsolationMethod::class, 'bacteria_isolation_method_id', 'id');
    }

    /**
     * Get the phenotype determination method record.
     */
    public function phenotypeDeterminationMethod()
    {
        return $this->belongsTo(DataPhenotypeDeterminationMethod::class, 'phenotype_determination_method_id', 'id');
    }

    /**
     * Get the interpretation criteria record.
     */
    public function interpretationCriteria()
    {
        return $this->belongsTo(DataInterpretationCriteria::class, 'interpretation_criteria_id', 'id');
    }
}
