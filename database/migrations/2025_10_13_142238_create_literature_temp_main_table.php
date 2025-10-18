<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('literature_temp_main', function (Blueprint $table) {
            $table->id();

            // Serial No. / Content independent identifier
            $table->integer('rowid')->nullable()->default(null)->comment('Serial No.');

            // Foreign key to susdat_substances (via code matching)
            $table->foreignId('substance_id')->nullable()->default(null)->constrained('susdat_substances')->onDelete('restrict');

            // Species information
            $table->foreignId('species_id')->nullable()->default(null)->constrained('list_species')->onDelete('restrict');
            $table->foreignId('common_name_id')->nullable()->default(null)->constrained('list_common_names')->onDelete('restrict');

            // Bibliographic source
            $table->text('title')->nullable()->default(null)->comment('Title of paper');
            $table->text('first_author')->nullable()->default(null)->comment('Name of first author only');
            $table->integer('year')->nullable()->default(null)->comment('Year of publishing');
            $table->text('doi')->nullable()->default(null)->comment('DOI');

            // Biota information
            $table->foreignId('sex_id')->nullable()->default(null)->constrained('list_biota_sexs')->onDelete('restrict');
            $table->text('diet_as_described_in_paper')->nullable()->default(null);
            $table->text('trophic_level_as_described_in_paper')->nullable()->default(null);
            $table->foreignId('life_stage_id')->nullable()->default(null)->constrained('list_life_stages')->onDelete('restrict');
            $table->text('age_in_days')->nullable()->default(null)->comment('Age in days, data string due to ranges');
            $table->integer('x_of_replicates')->nullable()->default(null)->comment('Replicate size');

            // Monitoring and sampling information
            $table->text('type_of_monitoring')->nullable()->default(null)->comment('Context of sampling');
            $table->text('active_passive_sampling')->nullable()->default(null)->comment('Was sampling active or passive');

            // Location information
            $table->foreignId('country_id')->nullable()->default(null)->constrained('list_countries')->onDelete('restrict');
            $table->text('region_city')->nullable()->default(null)->comment('Region/city in which was sampled');

            // Health and habitat
            $table->text('health_status')->nullable()->default(null)->comment('Health condition and if available method of killing');
            $table->foreignId('habitat_type_id')->nullable()->default(null)->constrained('list_habitat_types')->onDelete('restrict');
            $table->text('reported_distance_to_industry')->nullable()->default(null);

            // Pesticide treatment information
            $table->text('last_pesticide_treatment')->nullable()->default(null);
            $table->text('pesticide_used_in_treatment')->nullable()->default(null);

            // Tissue and measurement
            $table->foreignId('tissue_id')->nullable()->default(null)->constrained('list_tissues')->onDelete('restrict');
            $table->text('basis_of_measurement')->nullable()->default(null)->comment('Measurement reference: lw:lipid weight, ww: wet weight, dw: dry weight');
            $table->text('analytical_method')->nullable()->default(null)->comment('Analytical method used for quantification');
            $table->text('storage_temp_c')->nullable()->default(null)->comment('Temperature for storage of sample');

            // Detection limits
            $table->text('lod')->nullable()->default(null)->comment('Limit of detection');
            $table->text('lod_unit')->nullable()->default(null)->comment('Unit of lod');
            $table->text('loq')->nullable()->default(null)->comment('Limit of quantification');
            $table->text('loq_unit')->nullable()->default(null)->comment('Unit of loq');

            // Sample information
            $table->text('pooled')->nullable()->default(null)->comment('Denotes with "n" or "y" if a sample is a pooled sample');
            $table->text('x_of_subsamples')->nullable()->default(null)->comment('Number of samples pooled');
            $table->text('sd')->nullable()->default(null)->comment('Standard deviation of the concentration reported');
            $table->foreignId('type_of_numeric_quantity_id')->nullable()->default(null)->constrained('list_type_of_numeric_quantities')->onDelete('restrict');

            // Range information
            $table->text('range_min')->nullable()->default(null)->comment('Reported minimum value in range');
            $table->text('range_max')->nullable()->default(null)->comment('Reported maximum value in range');
            $table->text('reported_range_min')->nullable()->default(null)->comment('Minimum value reported in a range');
            $table->text('type_of_range_max')->nullable()->default(null)->comment('Maximum value reported in a range');

            // Concentration
            $table->foreignId('concentration_units_id')->nullable()->default(null)->constrained('list_concentration_units')->onDelete('restrict');
            $table->text('frequency_of_detection')->nullable()->default(null)->comment('Amount of positive samples in aggregate sample');
            $table->text('raw_data_available')->nullable()->default(null)->comment('Denotes if raw data available in publication');

            // Comments and identifiers
            $table->text('comment')->nullable()->default(null);
            $table->text('nest_field_if_not_dicernable')->nullable()->default(null)->comment('Identifier for non-independent samples');
            $table->text('chain_id_if_paper_has_chain')->nullable()->default(null)->comment('ID for the trophic chain sampled');

            // Sampling dates
            $table->integer('start_of_sampling_day')->nullable()->default(null);
            $table->text('start_of_sampling_month')->nullable()->default(null);
            $table->text('start_of_sampling_year')->nullable()->default(null);
            $table->integer('end_of_sampling_day')->nullable()->default(null);
            $table->text('end_of_sampling_month')->nullable()->default(null);
            $table->text('end_of_sampling_year')->nullable()->default(null);

            // Coordinates (10 latitude and 10 longitude fields)
            $table->text('imputed_coordinates')->nullable()->default(null)->comment('Indicator if coordinate is imputed or not');
            $table->decimal('latitude_decimal', 10, 8)->nullable()->default(null)->comment('Latitude in decimal degrees');
            $table->decimal('longitude_decimal', 11, 8)->nullable()->default(null)->comment('Longitude in decimal degrees');
            $table->text('latitude_1')->nullable()->default(null);
            $table->text('latitude_2')->nullable()->default(null);
            $table->text('latitude_3')->nullable()->default(null);
            $table->text('latitude_4')->nullable()->default(null);
            $table->text('latitude_5')->nullable()->default(null);
            $table->text('latitude_6')->nullable()->default(null);
            $table->text('latitude_7')->nullable()->default(null);
            $table->text('latitude_8')->nullable()->default(null);
            $table->text('latitude_9')->nullable()->default(null);
            $table->text('latitude_10')->nullable()->default(null);
            $table->text('longitude_1')->nullable()->default(null);
            $table->text('longitude_2')->nullable()->default(null);
            $table->text('longitude_3')->nullable()->default(null);
            $table->text('longitude_4')->nullable()->default(null);
            $table->text('longitude_5')->nullable()->default(null);
            $table->text('longitude_6')->nullable()->default(null);
            $table->text('longitude_7')->nullable()->default(null);
            $table->text('longitude_8')->nullable()->default(null);
            $table->text('longitude_9')->nullable()->default(null);
            $table->text('longitude_10')->nullable()->default(null);

            // Habitat and species classification
            $table->text('habitat_class')->nullable()->default(null)->comment('Habitat grouped based on report in paper');
            $table->text('dietary_preference')->nullable()->default(null)->comment('Species Dietary Preference imputed by trait databases');

            // IDs and measurements
            $table->integer('individual_id')->nullable()->default(null)->comment('ID number for an individual');
            $table->text('unique_measurement')->nullable()->default(null)->comment('Temporary unique identifier');
            $table->text('concentrationlevel')->nullable()->default(null)->comment('Level of concentration in original concentration');
            $table->text('sample_id')->nullable()->default(null)->comment('Sample ID of a tissue');
            $table->text('reported_concentration')->nullable()->default(null)->comment('Concentration reported in paper');
            $table->double('freq_numeric')->nullable()->default(null)->comment('Frequency of positive hits');
            $table->double('n_0')->nullable()->default(null)->comment('Number of negative hits');

            // Phylogenetic data
            $table->text('kingdom')->nullable()->default(null)->comment('Phylogenetic data');
            $table->text('phylum')->nullable()->default(null)->comment('Phylogenetic data');
            $table->text('order')->nullable()->default(null)->comment('Phylogenetic data');
            $table->text('genus')->nullable()->default(null)->comment('Phylogenetic data');
            $table->text('class_phyl')->nullable()->default(null)->comment('Phylogenetic data');
            $table->text('source_trait')->nullable()->default(null)->comment('Source for trait data');

            // Chemical information
            $table->text('class')->nullable()->default(null)->comment('Chemical class');
            $table->text('source_chem')->nullable()->default(null)->comment('Source for chem data');
            $table->foreignId('use_chem_id')->nullable()->default(null)->constrained('list_use_categories')->onDelete('restrict');
            $table->text('is_transformation_product')->nullable()->default(null)->comment('Denotes if chemical active substance or transformation product');
            $table->text('parent')->nullable()->default(null)->comment('Parent compound of transformation product');
            $table->text('is_group')->nullable()->default(null)->comment('Denotes if chemical is grouped (Yes,NA)');

            // Calculated concentrations
            $table->double('water_content')->nullable()->default(null)->comment('Water content of tissue [%]');
            $table->double('ww_conc_ng')->nullable()->default(null)->comment('Concentration in ng/g ww');
            $table->double('ww_lod_ng')->nullable()->default(null)->comment('LOD in ng/g ww');
            $table->double('ww_loq_ng')->nullable()->default(null)->comment('LOQ in ng/g ww');
            $table->double('ww_sd_ng')->nullable()->default(null)->comment('SD in ng/g ww');
            $table->double('imputed_lod')->nullable()->default(null)->comment('Imputed Lod value in ng/g ww');
            $table->double('all_means_without_0')->nullable()->default(null)->comment('All Means without 0 in ng/g ww');
            $table->double('all_means_with_0')->nullable()->default(null)->comment('All Means with 0 in ng/g ww');

            // Chemical name
            $table->text('chemical_name')->nullable()->default(null)->comment('Name of chemical tested');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('literature_temp_main');
    }
};
