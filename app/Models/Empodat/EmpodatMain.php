<?php

namespace App\Models\Empodat;

use App\Models\Backend\File;
use App\Models\Susdat\Substance;
use App\Models\Empodat\AnalyticalMethod as EmpodatAnalyticalMethod;
use App\Models\List\ConcentrationIndicator;
use App\Models\List\Matrix;
use App\Models\Empodat\EmpodatMinor;
use App\Models\Empodat\EmpodatMatrixAir;
use App\Models\Empodat\EmpodatMatrixBiota;
use App\Models\Empodat\EmpodatMatrixSediments;
use App\Models\Empodat\EmpodatMatrixSewageSludge;
use App\Models\Empodat\EmpodatMatrixSoil;
use App\Models\Empodat\EmpodatMatrixSuspendedMatter;
use App\Models\Empodat\EmpodatMatrixWater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class EmpodatMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'empodat_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dct_analysis_id',
        'station_id',
        'matrix_id',
        'substance_id',
        'sampling_date_year',
        'concentration_indicator_id',
        'concentration_value',
        'method_id',
        'data_source_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'dct_analysis_id' => 'integer',
        'station_id' => 'integer',
        'matrix_id' => 'integer',
        'substance_id' => 'integer',
        'sampling_date_year' => 'integer',
        'concentration_indicator_id' => 'integer',
        'concentration_value' => 'float',
        'method_id' => 'integer',
        'data_source_id' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'formatted_sampling_date',
    ];

    /**
     * Get the concentration indicator associated with this record.
     */
    public function concentrationIndicator()
    {
        return $this->belongsTo(ConcentrationIndicator::class, 'concentration_indicator_id');
    }

    /**
     * Get the station associated with this record.
     */
    public function station()
    {
        return $this->belongsTo(EmpodatStation::class, 'station_id');
    }

    /**
     * Get the substance associated with this record.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    /**
     * Get the matrix associated with this record.
     */
    public function matrix()
    {
        return $this->belongsTo(Matrix::class, 'matrix_id');
    }

    /**
     * Get the analytical method associated with this record.
     */
    public function analyticalMethod()
    {
        return $this->belongsTo(EmpodatAnalyticalMethod::class, 'method_id');
    }

    /**
     * Get the data source associated with this record.
     */
    public function dataSource()
    {
        return $this->belongsTo(DataSources::class, 'data_source_id');
    }

    /**
     * Get the minor details associated with this record.
     */
    public function minor()
    {
        return $this->hasOne(EmpodatMinor::class, 'id', 'id');
    }

    /**
     * Get the air matrix data associated with this record.
     */
    public function matrixAir()
    {
        return $this->hasOne(EmpodatMatrixAir::class, 'dct_analysis_id', 'dct_analysis_id');
    }

    /**
     * Get the biota matrix data associated with this record.
     */
    public function matrixBiota()
    {
        return $this->hasOne(EmpodatMatrixBiota::class, 'dct_analysis_id', 'dct_analysis_id');
    }

    /**
     * Get the sediments matrix data associated with this record.
     */
    public function matrixSediments()
    {
        return $this->hasOne(EmpodatMatrixSediments::class, 'dct_analysis_id', 'dct_analysis_id');
    }

    /**
     * Get the sewage sludge matrix data associated with this record.
     */
    public function matrixSewageSludge()
    {
        return $this->hasOne(EmpodatMatrixSewageSludge::class, 'dct_analysis_id', 'dct_analysis_id');
    }

    /**
     * Get the soil matrix data associated with this record.
     */
    public function matrixSoil()
    {
        return $this->hasOne(EmpodatMatrixSoil::class, 'dct_analysis_id', 'dct_analysis_id');
    }

    /**
     * Get the suspended matter matrix data associated with this record.
     */
    public function matrixSuspendedMatter()
    {
        return $this->hasOne(EmpodatMatrixSuspendedMatter::class, 'dct_analysis_id', 'dct_analysis_id');
    }

    /**
     * Get the water matrix data associated with this record.
     */
    public function matrixWater()
    {
        return $this->hasOne(EmpodatMatrixWater::class, 'dct_analysis_id', 'dct_analysis_id');
    }

    /**
     * The files that are associated with this empodat record.
     */
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'empodat_main_file', 'empodat_main_id', 'file_id');
    }

    /**
     * Scope to filter by countries through station relationship
     */
    public function scopeByCountries($query, array $countryIds)
    {
        if (empty($countryIds)) {
            return $query;
        }

        return $query->whereHas('station.country', function ($subQuery) use ($countryIds) {
            $subQuery->whereIn('id', $countryIds);
        });
    }

    /**
     * Scope to filter by matrices
     */
    public function scopeByMatrices($query, array $matrixIds)
    {
        if (empty($matrixIds)) {
            return $query;
        }

        return $query->whereIn('matrix_id', $matrixIds);
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
     * Scope to filter by NORMAN relevant substances only
     */
    public function scopeNormanRelevant($query)
    {
        return $query->whereHas('substance', function ($subQuery) {
            $subQuery->where('relevant_to_norman', 1);
        });
    }

    /**
     * Scope to filter by concentration indicators
     */
    public function scopeByConcentrationIndicators($query, array $indicatorIds)
    {
        if (empty($indicatorIds)) {
            return $query;
        }

        return $query->whereIn('concentration_indicator_id', $indicatorIds);
    }

    /**
     * Scope to filter by year range
     */
    public function scopeByYearRange($query, $yearFrom = null, $yearTo = null)
    {
        if (!is_null($yearFrom)) {
            $query->where('sampling_date_year', '>=', $yearFrom);
        }

        if (!is_null($yearTo)) {
            $query->where('sampling_date_year', '<=', $yearTo);
        }

        return $query;
    }

    /**
     * Scope to filter by substance categories
     */
    public function scopeByCategories($query, array $categoryIds)
    {
        if (empty($categoryIds)) {
            return $query;
        }

        return $query->whereHas('substance.categories', function ($subQuery) use ($categoryIds) {
            $subQuery->whereIn('susdat_categories.id', $categoryIds);
        });
    }

    /**
     * Scope to filter by SLE sources
     */
    public function scopeBySources($query, array $sourceIds)
    {
        if (empty($sourceIds)) {
            return $query;
        }

        return $query->whereHas('substance.sources', function ($subQuery) use ($sourceIds) {
            $subQuery->whereIn('sle_sources.id', $sourceIds);
        });
    }

    /**
     * Scope to filter by data source properties
     */
    public function scopeByDataSourceFilters($query, array $typeIds = [], array $labIds = [], array $orgIds = [])
    {
        $hasFilters = !empty($typeIds) || !empty($labIds) || !empty($orgIds);

        if (!$hasFilters) {
            return $query;
        }

        return $query->whereHas('dataSource', function ($subQuery) use ($typeIds, $labIds, $orgIds) {
            if (!empty($typeIds)) {
                $subQuery->whereIn('type_data_source_id', $typeIds);
            }

            if (!empty($labIds)) {
                $subQuery->whereIn('laboratory1_id', $labIds);
            }

            if (!empty($orgIds)) {
                $subQuery->whereIn('organisation_id', $orgIds);
            }
        });
    }

    /**
     * Scope to filter by analytical method
     */
    public function scopeByAnalyticalMethods($query, array $methodIds)
    {
        if (empty($methodIds)) {
            return $query;
        }

        return $query->whereHas('analyticalMethod', function ($subQuery) use ($methodIds) {
            $subQuery->whereIn('analytical_method_id', $methodIds);
        });
    }

    /**
     * Scope to filter by quality ratings
     */
    public function scopeByQualityRatings($query, $ratings)
    {
        if (empty($ratings)) {
            return $query;
        }

        return $query->whereHas('analyticalMethod', function ($subQuery) use ($ratings) {
            $subQuery->where(function ($ratingQuery) use ($ratings) {
                foreach ($ratings as $rating) {
                    $ratingQuery->orWhere(function ($individualRating) use ($rating) {
                        $individualRating->where('rating', '>=', $rating->min_rating)
                            ->where('rating', '<', $rating->max_rating);
                    });
                }
            });
        });
    }

    public function scopeByFiles($query, $fileIds)
    {
        // Convert to array if not already
        if (!is_array($fileIds)) {
            $fileIds = [$fileIds];
        }
        
        if (empty($fileIds)) {
            return $query;
        }

        return $query->whereHas('files', function ($subQuery) use ($fileIds) {
            $subQuery->whereIn('files.id', $fileIds);
        });
    }

    /**
     * Scope to eager load all search-related relationships
     */
    public function scopeWithSearchRelations($query)
    {
        return $query->with([
            'concentrationIndicator',
            'substance',
            'matrix',
            'station.country',
            'analyticalMethod',
            'dataSource',
            'files',
            'minor',
            'matrixAir',
            'matrixBiota',
            'matrixSediments',
            'matrixSewageSludge',
            'matrixSoil',
            'matrixSuspendedMatter',
            'matrixWater'
        ]);
    }

    /**
     * Get the formatted concentration value with indicator.
     * 
     * @return string|null
     */
    public function getFormattedConcentrationAttribute()
    {
        if ($this->concentration_value === null) {
            return null;
        }

        $indicator = $this->concentrationIndicator ? $this->concentrationIndicator->symbol : '';
        return $indicator . number_format($this->concentration_value, 4);
    }

    /**
     * Get the formatted sampling date.
     * 
     * @return string
     */
    public function getFormattedSamplingDateAttribute()
    {
        // First try to get the full date from minor relationship
        if ($this->minor && $this->minor->sampling_date) {
            return Carbon::parse($this->minor->sampling_date)->format('Y-m-d');
        }
        
        // Fallback to the year if no minor date available
        if ($this->sampling_date_year) {
            return (string) $this->sampling_date_year;
        }
        
        // Final fallback
        return 'N/A';
    }
}