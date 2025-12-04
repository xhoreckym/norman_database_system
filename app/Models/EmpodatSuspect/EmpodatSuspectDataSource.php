<?php

declare(strict_types=1);

namespace App\Models\EmpodatSuspect;

use App\Models\Backend\File;
use App\Models\List\DataSourceLaboratory;
use App\Models\List\DataSourceOrganisation;
use App\Models\List\TypeDataSource;
use App\Models\List\TypeMonitoring;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpodatSuspectDataSource extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'empodat_suspect_data_source';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'file_id',
        'source_data_id',
        'monitoring_type_id',
        'organisation_id',
        'laboratory_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'file_id' => 'integer',
        'source_data_id' => 'integer',
        'monitoring_type_id' => 'integer',
        'organisation_id' => 'integer',
        'laboratory_id' => 'integer',
    ];

    /**
     * Get the file associated with this data source.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Get the type of data source (e.g., "Monitoring data").
     */
    public function sourceData(): BelongsTo
    {
        return $this->belongsTo(TypeDataSource::class, 'source_data_id');
    }

    /**
     * Get the type of monitoring (e.g., "Investigative").
     */
    public function monitoringType(): BelongsTo
    {
        return $this->belongsTo(TypeMonitoring::class, 'monitoring_type_id');
    }

    /**
     * Get the organisation associated with this data source.
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(DataSourceOrganisation::class, 'organisation_id');
    }

    /**
     * Get the laboratory associated with this data source.
     */
    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(DataSourceLaboratory::class, 'laboratory_id');
    }
}
