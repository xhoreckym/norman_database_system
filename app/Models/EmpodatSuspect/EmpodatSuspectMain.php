<?php

namespace App\Models\EmpodatSuspect;

use App\Models\Empodat\EmpodatStation;
use App\Models\Susdat\Substance;
use App\Models\Backend\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmpodatSuspectMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'empodat_suspect_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'substance_id',
        'xlsx_station_mapping_id',
        'station_id',
        'concentration',
        'ip',
        'ip_max',
        'based_on_hrms_library',
        'units',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'substance_id' => 'integer',
        'xlsx_station_mapping_id' => 'integer',
        'station_id' => 'integer',
        'concentration' => 'float',
        'ip_max' => 'float',
        'based_on_hrms_library' => 'boolean',
    ];

    /**
     * Get the substance associated with this record.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    /**
     * Get the station associated with this record.
     */
    public function station()
    {
        return $this->belongsTo(EmpodatStation::class, 'station_id');
    }

    /**
     * Get the XLSX station mapping associated with this record.
     */
    public function xlsxStationMapping()
    {
        return $this->belongsTo(EmpodatSuspectXlsxStationsMapping::class, 'xlsx_station_mapping_id');
    }

    /**
     * Get the files associated with this empodat_suspect record.
     */
    public function files()
    {
        return $this->belongsToMany(
            File::class,
            'file_empodat_suspect_main',
            'empodat_suspect_main_id',
            'file_id'
        )->withTimestamps();
    }

    /**
     * Scope to filter by stations
     */
    public function scopeByStations($query, array $stationIds)
    {
        if (empty($stationIds)) {
            return $query;
        }

        return $query->whereIn('station_id', $stationIds);
    }

    /**
     * Scope to filter by substances
     */
    public function scopeBySubstances($query, array $substanceIds)
    {
        if (empty($substanceIds)) {
            return $query;
        }

        return $query->whereIn('substance_id', $substanceIds);
    }

    /**
     * Scope to filter by substance categories
     */
    public function scopeByCategories($query, array $categoryIds)
    {
        if (empty($categoryIds)) {
            return $query;
        }

        return $query->whereHas('substance.categories', function ($q) use ($categoryIds) {
            $q->whereIn('susdat_categories.id', $categoryIds);
        });
    }

    /**
     * Scope to filter by IP_max range (identification confidence)
     */
    public function scopeByIpMaxRange($query, $ipMaxMin = null, $ipMaxMax = null)
    {
        if (!is_null($ipMaxMin)) {
            $query->where('ip_max', '>=', $ipMaxMin);
        }

        if (!is_null($ipMaxMax)) {
            $query->where('ip_max', '<=', $ipMaxMax);
        }

        return $query;
    }

    /**
     * Scope to eager load all search-related relationships
     */
    public function scopeWithSearchRelations($query)
    {
        return $query->with([
            'station',
            'substance',
            'xlsxStationMapping',
        ]);
    }
}
