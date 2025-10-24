<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Literature Record Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Literature Record Information at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->id ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Norman SUS ID</h3>
                <p class="text-sm text-teal-800 font-mono">
                  @if ($record->substance && $record->substance->code)
                    <a href="{{ route('substances.show', $record->substance->id) }}" class="link-lime-text">
                      NS{{ $record->substance->code }}
                    </a>
                  @else
                    N/A
                  @endif
                </p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Chemical Name</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->substance->name ?? $record->chemical_name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Species</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->species->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Species (Latin)</h3>
                <p class="text-sm text-teal-800 font-mono font-italic">{{ $record->species->name_latin ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Species Class</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->species->class ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Country</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->country->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Concentration (ng/g ww)</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->ww_conc_ng !== null ? number_format($record->ww_conc_ng, 4) : 'N/A' }}</p>
              </div>
            </div>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full">
            <div class="flex justify-between items-center mb-4">
              <span class="text-xl font-bold">Complete Literature Record Details</span>
              @auth
                @role('super_admin|admin|literature')
                  <a class="link-edit" href="{{ route('literature.search.edit', $record->id) }}">
                    Edit
                  </a>
                @endrole
              @endauth
            </div>

            {{-- Bibliographic Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Bibliographic Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Title</h4>
                  <p class="text-sm text-gray-900">{{ $record->title ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">First Author</h4>
                  <p class="text-sm text-gray-900">{{ $record->first_author ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Year</h4>
                  <p class="text-sm text-gray-900">{{ $record->year ?? 'N/A' }}</p>
                </div>
                <div class="md:col-span-2">
                  <h4 class="text-sm font-medium text-gray-500 mb-1">DOI</h4>
                  <p class="text-sm text-gray-900">
                    @if ($record->doi)
                      <a href="https://doi.org/{{ $record->doi }}" target="_blank" class="link-lime-text">
                        {{ $record->doi }}
                      </a>
                    @else
                      N/A
                    @endif
                  </p>
                </div>
              </div>
            </div>

            {{-- Chemical Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Chemical Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Chemical Name (as reported)</h4>
                  <p class="text-sm text-gray-900">{{ $record->chemical_name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Chemical Class</h4>
                  <p class="text-sm text-gray-900">{{ $record->class ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Use Category</h4>
                  <p class="text-sm text-gray-900">{{ $record->useCategory->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Transformation Product</h4>
                  <p class="text-sm text-gray-900">{{ $record->is_transformation_product ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Parent Compound</h4>
                  <p class="text-sm text-gray-900">{{ $record->parent ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Is Group</h4>
                  <p class="text-sm text-gray-900">{{ $record->is_group ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Source (Chemical Data)</h4>
                  <p class="text-sm text-gray-900">{{ $record->source_chem ?? 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Species & Biological Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Species & Biological Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Common Name</h4>
                  <p class="text-sm text-gray-900">{{ $record->commonName->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Kingdom</h4>
                  <p class="text-sm text-gray-900">{{ $record->kingdom ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Phylum</h4>
                  <p class="text-sm text-gray-900">{{ $record->phylum ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Class (Phylogenetic)</h4>
                  <p class="text-sm text-gray-900">{{ $record->class_phyl ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Order</h4>
                  <p class="text-sm text-gray-900">{{ $record->order ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Genus</h4>
                  <p class="text-sm text-gray-900">{{ $record->genus ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Sex</h4>
                  <p class="text-sm text-gray-900">{{ $record->sex->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Life Stage</h4>
                  <p class="text-sm text-gray-900">{{ $record->lifeStage->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Age (days)</h4>
                  <p class="text-sm text-gray-900">{{ $record->age_in_days ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Health Status</h4>
                  <p class="text-sm text-gray-900">{{ $record->health_status ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Diet (as described)</h4>
                  <p class="text-sm text-gray-900">{{ $record->diet_as_described_in_paper ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Trophic Level</h4>
                  <p class="text-sm text-gray-900">{{ $record->trophic_level_as_described_in_paper ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Dietary Preference</h4>
                  <p class="text-sm text-gray-900">{{ $record->dietary_preference ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Source (Trait Data)</h4>
                  <p class="text-sm text-gray-900">{{ $record->source_trait ?? 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Location & Habitat Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Location & Habitat Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Region/City</h4>
                  <p class="text-sm text-gray-900">{{ $record->region_city ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Habitat Type</h4>
                  <p class="text-sm text-gray-900">{{ $record->habitatType->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Habitat Class</h4>
                  <p class="text-sm text-gray-900">{{ $record->habitat_class ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Distance to Industry</h4>
                  <p class="text-sm text-gray-900">{{ $record->reported_distance_to_industry ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Coordinates Imputed</h4>
                  <p class="text-sm text-gray-900">{{ $record->imputed_coordinates ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Latitude (Decimal)</h4>
                  <p class="text-sm text-gray-900">{{ $record->latitude_decimal ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Longitude (Decimal)</h4>
                  <p class="text-sm text-gray-900">{{ $record->longitude_decimal ?? 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Sampling Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Sampling Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Sampling Start Date</h4>
                  <p class="text-sm text-gray-900">
                    @php
                      $startDate = [];
                      if ($record->start_of_sampling_year) $startDate[] = $record->start_of_sampling_year;
                      if ($record->start_of_sampling_month) $startDate[] = $record->start_of_sampling_month;
                      if ($record->start_of_sampling_day) $startDate[] = $record->start_of_sampling_day;
                      echo !empty($startDate) ? implode('-', $startDate) : 'N/A';
                    @endphp
                  </p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Sampling End Date</h4>
                  <p class="text-sm text-gray-900">
                    @php
                      $endDate = [];
                      if ($record->end_of_sampling_year) $endDate[] = $record->end_of_sampling_year;
                      if ($record->end_of_sampling_month) $endDate[] = $record->end_of_sampling_month;
                      if ($record->end_of_sampling_day) $endDate[] = $record->end_of_sampling_day;
                      echo !empty($endDate) ? implode('-', $endDate) : 'N/A';
                    @endphp
                  </p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Type of Monitoring</h4>
                  <p class="text-sm text-gray-900">{{ $record->type_of_monitoring ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Active/Passive Sampling</h4>
                  <p class="text-sm text-gray-900">{{ $record->active_passive_sampling ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Last Pesticide Treatment</h4>
                  <p class="text-sm text-gray-900">{{ $record->last_pesticide_treatment ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Pesticide Used</h4>
                  <p class="text-sm text-gray-900">{{ $record->pesticide_used_in_treatment ?? 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Tissue & Measurement Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Tissue & Measurement Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Tissue</h4>
                  <p class="text-sm text-gray-900">{{ $record->tissue->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Basis of Measurement</h4>
                  <p class="text-sm text-gray-900">{{ $record->basis_of_measurement ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Analytical Method</h4>
                  <p class="text-sm text-gray-900">{{ $record->analytical_method ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Storage Temperature (°C)</h4>
                  <p class="text-sm text-gray-900">{{ $record->storage_temp_c ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Water Content (%)</h4>
                  <p class="text-sm text-gray-900">{{ $record->water_content !== null ? number_format($record->water_content, 2) : 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Concentration Data --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Concentration Data</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Reported Concentration</h4>
                  <p class="text-sm text-gray-900">{{ $record->reported_concentration ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Concentration Units</h4>
                  <p class="text-sm text-gray-900">{{ $record->concentrationUnit->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Concentration Level</h4>
                  <p class="text-sm text-gray-900">{{ $record->concentrationlevel ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Concentration (ng/g ww)</h4>
                  <p class="text-sm text-gray-900 font-semibold">{{ $record->ww_conc_ng !== null ? number_format($record->ww_conc_ng, 4) : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">LOD (ng/g ww)</h4>
                  <p class="text-sm text-gray-900">{{ $record->ww_lod_ng !== null ? number_format($record->ww_lod_ng, 4) : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">LOQ (ng/g ww)</h4>
                  <p class="text-sm text-gray-900">{{ $record->ww_loq_ng !== null ? number_format($record->ww_loq_ng, 4) : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Standard Deviation (ng/g ww)</h4>
                  <p class="text-sm text-gray-900">{{ $record->ww_sd_ng !== null ? number_format($record->ww_sd_ng, 4) : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">LOD (as reported)</h4>
                  <p class="text-sm text-gray-900">{{ $record->lod ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">LOD Unit</h4>
                  <p class="text-sm text-gray-900">{{ $record->lod_unit ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">LOQ (as reported)</h4>
                  <p class="text-sm text-gray-900">{{ $record->loq ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">LOQ Unit</h4>
                  <p class="text-sm text-gray-900">{{ $record->loq_unit ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">SD (as reported)</h4>
                  <p class="text-sm text-gray-900">{{ $record->sd ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Imputed LOD (ng/g ww)</h4>
                  <p class="text-sm text-gray-900">{{ $record->imputed_lod !== null ? number_format($record->imputed_lod, 4) : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">All Means Without 0 (ng/g ww)</h4>
                  <p class="text-sm text-gray-900">{{ $record->all_means_without_0 !== null ? number_format($record->all_means_without_0, 4) : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">All Means With 0 (ng/g ww)</h4>
                  <p class="text-sm text-gray-900">{{ $record->all_means_with_0 !== null ? number_format($record->all_means_with_0, 4) : 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Sample Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Sample Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Sample ID</h4>
                  <p class="text-sm text-gray-900">{{ $record->sample_id ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Individual ID</h4>
                  <p class="text-sm text-gray-900">{{ $record->individual_id ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Unique Measurement ID</h4>
                  <p class="text-sm text-gray-900">{{ $record->unique_measurement ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Pooled Sample</h4>
                  <p class="text-sm text-gray-900">{{ $record->pooled ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Number of Subsamples</h4>
                  <p class="text-sm text-gray-900">{{ $record->x_of_subsamples ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Number of Replicates</h4>
                  <p class="text-sm text-gray-900">{{ $record->x_of_replicates ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Type of Numeric Quantity</h4>
                  <p class="text-sm text-gray-900">{{ $record->typeOfNumericQuantity->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Frequency of Detection</h4>
                  <p class="text-sm text-gray-900">{{ $record->frequency_of_detection ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Freq. Numeric</h4>
                  <p class="text-sm text-gray-900">{{ $record->freq_numeric !== null ? number_format($record->freq_numeric, 2) : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">N (0 values)</h4>
                  <p class="text-sm text-gray-900">{{ $record->n_0 !== null ? number_format($record->n_0, 0) : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Raw Data Available</h4>
                  <p class="text-sm text-gray-900">{{ $record->raw_data_available ?? 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Range Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Range Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Range Min</h4>
                  <p class="text-sm text-gray-900">{{ $record->range_min ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Range Max</h4>
                  <p class="text-sm text-gray-900">{{ $record->range_max ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Reported Range Min</h4>
                  <p class="text-sm text-gray-900">{{ $record->reported_range_min ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Type of Range Max</h4>
                  <p class="text-sm text-gray-900">{{ $record->type_of_range_max ?? 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Additional Information --}}
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3 border-b pb-2">Additional Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Comment</h4>
                  <p class="text-sm text-gray-900">{{ $record->comment ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Nest Field (if not discernable)</h4>
                  <p class="text-sm text-gray-900">{{ $record->nest_field_if_not_dicernable ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Chain ID (if paper has chain)</h4>
                  <p class="text-sm text-gray-900">{{ $record->chain_id_if_paper_has_chain ?? 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500 mb-1">Row ID (original data)</h4>
                  <p class="text-sm text-gray-900">{{ $record->rowid ?? 'N/A' }}</p>
                </div>
              </div>
            </div>

            {{-- Back to Search Button --}}
            <div class="mt-6 flex justify-start">
              <a href="{{ route('literature.search.filter') }}" class="btn-clear">
                Back to Search
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
