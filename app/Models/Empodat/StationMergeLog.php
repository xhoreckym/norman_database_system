<?php

namespace App\Models\Empodat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StationMergeLog extends Model
{
    protected $table = 'empodat_station_merge_log';

    protected $fillable = [
        'deprecated_station_id',
        'canonical_station_id',
        'merge_reason',
        'deprecated_data',
        'merged_by',
    ];

    protected $casts = [
        'deprecated_data' => 'array',
    ];

    /**
     * Get the deprecated station
     */
    public function deprecatedStation()
    {
        return $this->belongsTo(EmpodatStation::class, 'deprecated_station_id');
    }

    /**
     * Get the canonical station
     */
    public function canonicalStation()
    {
        return $this->belongsTo(EmpodatStation::class, 'canonical_station_id');
    }

    /**
     * Get the user who performed the merge
     */
    public function mergedByUser()
    {
        return $this->belongsTo(User::class, 'merged_by');
    }
}
