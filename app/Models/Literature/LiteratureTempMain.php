<?php

namespace App\Models\Literature;

use App\Models\Backend\File;
use App\Models\List\BiotaSex;
use App\Models\List\CommonName;
use App\Models\List\ConcentrationUnit;
use App\Models\List\Country;
use App\Models\List\HabitatType;
use App\Models\List\LifeStage;
use App\Models\List\Matrix;
use App\Models\List\Tissue;
use App\Models\List\UseCategory;
use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiteratureTempMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'literature_temp_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_id',
        'rowid',
        'substance_id',
        'species_id',
        'common_name_id',
        'title',
        'first_author',
        'year',
        'doi',
        'sex_id',
        'diet_as_described_in_paper',
        'trophic_level_as_described_in_paper',
        'life_stage_id',
        'age_in_days',
        'x_of_replicates',
        'type_of_monitoring',
        'active_passive_sampling',
        'country_id',
        'region_city',
        'health_status',
        'habitat_type_id',
        'reported_distance_to_industry',
        'last_pesticide_treatment',
        'pesticide_used_in_treatment',
        'tissue_id',
        'matrix_id',
        'basis_of_measurement',
        'analytical_method',
        'storage_temp_c',
        'chemical_name',
        'concentration_unit_raw',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'file_id' => 'integer',
        'rowid' => 'integer',
        'substance_id' => 'integer',
        'species_id' => 'integer',
        'common_name_id' => 'integer',
        'year' => 'integer',
        'sex_id' => 'integer',
        'life_stage_id' => 'integer',
        'x_of_replicates' => 'integer',
        'country_id' => 'integer',
        'habitat_type_id' => 'integer',
        'tissue_id' => 'integer',
        'matrix_id' => 'integer',
        'concentration_units_id' => 'integer',
        'use_chem_id' => 'integer',
        'individual_id' => 'integer',
        'start_of_sampling_day' => 'integer',
        'end_of_sampling_day' => 'integer',
        'freq_numeric' => 'float',
        'n_0' => 'float',
        'water_content' => 'float',
        'ww_conc_ng' => 'float',
        'ww_lod_ng' => 'float',
        'ww_loq_ng' => 'float',
        'ww_sd_ng' => 'float',
        'imputed_lod' => 'float',
        'all_means_without_0' => 'float',
        'all_means_with_0' => 'float',
    ];

    /**
     * Get the country associated with this record.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Get the species associated with this record.
     */
    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

    /**
     * Get the substance associated with this record.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    /**
     * Get the tissue associated with this record.
     */
    public function tissue()
    {
        return $this->belongsTo(Tissue::class, 'tissue_id');
    }

    /**
     * Get the matrix associated with this record.
     */
    public function matrix()
    {
        return $this->belongsTo(Matrix::class, 'matrix_id');
    }

    /**
     * Get the sex associated with this record.
     */
    public function sex()
    {
        return $this->belongsTo(BiotaSex::class, 'sex_id');
    }

    /**
     * Get the life stage associated with this record.
     */
    public function lifeStage()
    {
        return $this->belongsTo(LifeStage::class, 'life_stage_id');
    }

    /**
     * Get the habitat type associated with this record.
     */
    public function habitatType()
    {
        return $this->belongsTo(HabitatType::class, 'habitat_type_id');
    }

    /**
     * Get the concentration unit associated with this record.
     */
    public function concentrationUnit()
    {
        return $this->belongsTo(ConcentrationUnit::class, 'concentration_units_id');
    }

    /**
     * Get the common name associated with this record.
     */
    public function commonName()
    {
        return $this->belongsTo(CommonName::class, 'common_name_id');
    }

    /**
     * Get the use category associated with this record.
     */
    public function useCategory()
    {
        return $this->belongsTo(UseCategory::class, 'use_chem_id');
    }

    /**
     * Get the type of numeric quantity associated with this record.
     */
    public function typeOfNumericQuantity()
    {
        return $this->belongsTo(\App\Models\Literature\TypeOfNumericQuantity::class, 'type_of_numeric_quantity_id');
    }

    /**
     * Get the file associated with this literature record.
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Scope to filter by countries
     */
    public function scopeByCountries($query, array $countryIds)
    {
        if (empty($countryIds)) {
            return $query;
        }

        return $query->whereIn('country_id', $countryIds);
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
     * Scope to filter by species
     */
    public function scopeBySpecies($query, array $speciesIds)
    {
        if (empty($speciesIds)) {
            return $query;
        }

        return $query->whereIn('species_id', $speciesIds);
    }

    /**
     * Scope to filter by type of numeric quantity
     */
    public function scopeByTypeOfNumericQuantity($query, array $typeOfNumericQuantityIds)
    {
        if (empty($typeOfNumericQuantityIds)) {
            return $query;
        }

        return $query->whereIn('type_of_numeric_quantity_id', $typeOfNumericQuantityIds);
    }

    /**
     * Scope to filter by species class
     */
    public function scopeByClasses($query, array $classes)
    {
        if (empty($classes)) {
            return $query;
        }

        return $query->whereHas('species', function ($q) use ($classes) {
            $q->whereIn('class', $classes);
        });
    }

    /**
     * Scope to filter by tissues
     */
    public function scopeByTissues($query, array $tissueIds)
    {
        if (empty($tissueIds)) {
            return $query;
        }

        return $query->whereIn('tissue_id', $tissueIds);
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
     * Scope to filter by files
     */
    public function scopeByFiles($query, array $fileIds)
    {
        if (empty($fileIds)) {
            return $query;
        }

        return $query->whereIn('file_id', $fileIds);
    }

    /**
     * Scope to filter by projects (through file relationship)
     */
    public function scopeByProjects($query, array $projectIds)
    {
        if (empty($projectIds)) {
            return $query;
        }

        return $query->whereHas('file', function ($q) use ($projectIds) {
            $q->whereIn('project_id', $projectIds);
        });
    }

    /**
     * Scope to filter by year range
     */
    public function scopeByYearRange($query, $yearFrom = null, $yearTo = null)
    {
        if (! is_null($yearFrom)) {
            $query->where('year', '>=', $yearFrom);
        }

        if (! is_null($yearTo)) {
            $query->where('year', '<=', $yearTo);
        }

        return $query;
    }

    /**
     * Scope to eager load all search-related relationships
     */
    public function scopeWithSearchRelations($query)
    {
        return $query->with([
            'country',
            'species',
            'substance',
            'tissue',
            'sex',
            'lifeStage',
            'habitatType',
            'commonName',
            'useCategory',
        ]);
    }
}
