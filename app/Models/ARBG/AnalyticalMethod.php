<?php

declare(strict_types=1);

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticalMethod extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'arbg_analytical_method';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'method_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'method_id',
        'type_of_sample_id',
        'type_of_sample_other',
        'volume_of_sample_used_for_dna_extraction',
        'method_used_for_dna_extraction',
        'targeted_analysis_id',
        'targeted_analysis_other',
        'non_targeted_analysis_id',
        'non_targeted_analysis_other',
        'analysis_of_pooled_dna_extracts',
        'analysis_of_pooled_dna_extracts_specify',
        'dna',
        'limit_of_detection',
        'limit_of_quantification',
        'uncertainty_of_the_quantification',
        'efficiency',
        'sequencing_read_depth',
        'analytical_method_id',
        'analytical_method_other',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'method_id' => 'integer',
        'type_of_sample_id' => 'integer',
        'targeted_analysis_id' => 'integer',
        'non_targeted_analysis_id' => 'integer',
        'analytical_method_id' => 'integer',
    ];

    /**
     * Get the analytical method type (lookup) associated with this method.
     */
    public function analyticalMethodType()
    {
        return $this->belongsTo(DataAnalyticalMethod::class, 'analytical_method_id', 'id');
    }

    /**
     * Get the type of sample (lookup) associated with this method.
     */
    public function typeOfSample()
    {
        return $this->belongsTo(DataTypeOfSample::class, 'type_of_sample_id', 'id');
    }

    /**
     * Get the targeted analysis (lookup) associated with this method.
     */
    public function targetedAnalysis()
    {
        return $this->belongsTo(DataTargetedAnalysis::class, 'targeted_analysis_id', 'id');
    }

    /**
     * Get the non-targeted analysis (lookup) associated with this method.
     */
    public function nonTargetedAnalysis()
    {
        return $this->belongsTo(DataNonTargetedAnalysis::class, 'non_targeted_analysis_id', 'id');
    }
}
