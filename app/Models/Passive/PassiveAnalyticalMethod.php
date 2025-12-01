<?php

declare(strict_types=1);

namespace App\Models\Passive;

use Illuminate\Database\Eloquent\Model;

class PassiveAnalyticalMethod extends Model
{
    protected $table = 'passive_analytical_method';

    protected $fillable = [
        'am_unit',
        'am_detection_limit',
        'am_quantification_limit',
        'dpm_id',
        'dpm_other',
        'dam_id',
        'dam_other',
        'dsm_id',
        'dsm_number',
        'dsm_other',
        'dp_id',
        'am_extraction_recovery_correction',
        'am_field_blank_check',
        'am_lab_iso17025',
        'am_lab_accredited',
        'am_interlab_studies',
        'am_interlab_summary',
        'am_control_charts',
        'am_authority_control',
        'am_remark',
    ];
}
