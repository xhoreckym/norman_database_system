<?php

declare(strict_types=1);

namespace App\Models\Indoor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndoorAnalyticalMethod extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'indoor_analytical_method';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id_method';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'am_lod',
        'am_loq',
        'am_unit',
        'am_uncertainty_loq',
        'dcf_id',
        'dsm1_id',
        'dsm1_other',
        'dsm2_id',
        'dsm2_other',
        'dpm_id',
        'dpm_other',
        'dam_id',
        'dam_other',
        'dsm_id',
        'dsm_other',
        'am_number',
        'am_validated_method',
        'am_corrected_recovery',
        'am_field_blank',
        'am_iso',
        'am_given_analyte',
        'am_laboratory_participate',
        'am_summary_performance',
        'am_control_charts',
        'am_authority',
        'am_remark',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'am_lod' => 'float',
        'am_loq' => 'float',
        'dcf_id' => 'integer',
        'dsm1_id' => 'integer',
        'dsm2_id' => 'integer',
        'dpm_id' => 'integer',
        'dam_id' => 'integer',
        'dsm_id' => 'integer',
    ];

    /**
     * Get the coverage factor lookup.
     */
    public function coverageFactor(): BelongsTo
    {
        return $this->belongsTo(IndoorDataDcf::class, 'dcf_id', 'id');
    }

    /**
     * Get the sampling method 1 lookup.
     */
    public function samplingMethod1(): BelongsTo
    {
        return $this->belongsTo(IndoorDataDsm1::class, 'dsm1_id', 'id');
    }

    /**
     * Get the sampling method 2 lookup.
     */
    public function samplingMethod2(): BelongsTo
    {
        return $this->belongsTo(IndoorDataDsm2::class, 'dsm2_id', 'id');
    }

    /**
     * Get the sample preparation method lookup.
     */
    public function samplePreparationMethod(): BelongsTo
    {
        return $this->belongsTo(IndoorDataDpm::class, 'dpm_id', 'id');
    }

    /**
     * Get the analytical method lookup.
     */
    public function analyticalMethod(): BelongsTo
    {
        return $this->belongsTo(IndoorDataDam::class, 'dam_id', 'id');
    }

    /**
     * Get the standardised method lookup.
     */
    public function standardisedMethod(): BelongsTo
    {
        return $this->belongsTo(IndoorDataDsm::class, 'dsm_id', 'id');
    }

    /**
     * Get formatted LOD with unit.
     */
    public function getFormattedLodAttribute(): ?string
    {
        if (empty($this->am_lod) && $this->am_lod !== 0.0) {
            return null;
        }

        return $this->am_lod.' '.($this->am_unit ?? '');
    }

    /**
     * Get formatted LOQ with unit.
     */
    public function getFormattedLoqAttribute(): ?string
    {
        if (empty($this->am_loq) && $this->am_loq !== 0.0) {
            return null;
        }

        return $this->am_loq.' '.($this->am_unit ?? '');
    }
}
