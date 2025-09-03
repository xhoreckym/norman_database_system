<x-app-layout>
  <x-slot name="header">
    @include('sars.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
              {{ session('success') }}
            </div>
          @endif

                      <div class="mb-6 flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-800">Record ID: {{ $sars->id }}</h3>
                    <div class="flex space-x-2">
        @if (auth()->check() && 
             (auth()->user()->hasRole('super_admin') || 
              auth()->user()->hasRole('admin') || 
              auth()->user()->hasRole('sars')))
          <a href="{{ route('sars.search.edit', $sars->id) }}" class="link-edit">
            <i class="fas fa-edit mr-1"></i> Edit
          </a>
        @endif
        <a href="{{ route('sars.search.search') }}" class="link-lime">
          <i class="fas fa-arrow-left mr-1"></i> Back to Search
        </a>
      </div>
            </div>

          <div class="overflow-x-auto">
            <table class="table-standard">
              <tbody>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Type of Data</td>
                  <td class="p-2">{{ $sars->type_of_data ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Data Provider</td>
                  <td class="p-2">{{ $sars->data_provider ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Contact Person</td>
                  <td class="p-2">{{ $sars->contact_person ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Address of Contact</td>
                  <td class="p-2">{{ $sars->address_of_contact ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Email</td>
                  <td class="p-2">{{ $sars->email ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Laboratory</td>
                  <td class="p-2">{{ $sars->laboratory ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Country</td>
                  <td class="p-2">{{ $sars->name_of_country ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">City</td>
                  <td class="p-2">{{ $sars->name_of_city ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Station Name</td>
                  <td class="p-2">{{ $sars->station_name ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">National Code</td>
                  <td class="p-2">{{ $sars->national_code ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Relevant EC Code (WISE)</td>
                  <td class="p-2">{{ $sars->relevant_ec_code_wise ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Relevant EC Code (Other)</td>
                  <td class="p-2">{{ $sars->relevant_ec_code_other ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Other Code</td>
                  <td class="p-2">{{ $sars->other_code ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Latitude</td>
                  <td class="p-2">{{ $sars->latitude ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Longitude</td>
                  <td class="p-2">{{ $sars->longitude ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Altitude</td>
                  <td class="p-2">{{ $sars->altitude ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Design Capacity</td>
                  <td class="p-2">{{ $sars->design_capacity ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Population Served</td>
                  <td class="p-2">{{ $sars->population_served ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Catchment Size</td>
                  <td class="p-2">{{ $sars->catchment_size ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">GDP</td>
                  <td class="p-2">{{ $sars->gdp ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">People Positive</td>
                  <td class="p-2">{{ $sars->people_positive ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">People Recovered</td>
                  <td class="p-2">{{ $sars->people_recovered ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">People Positive Past</td>
                  <td class="p-2">{{ $sars->people_positive_past ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">People Recovered Past</td>
                  <td class="p-2">{{ $sars->people_recovered_past ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Sample Matrix</td>
                  <td class="p-2">{{ $sars->sample_matrix ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Sampling Date From</td>
                  <td class="p-2">
                    @if($sars->sample_from_year && $sars->sample_from_month && $sars->sample_from_day)
                      {{ $sars->sample_from_year }}-{{ $sars->sample_from_month }}-{{ $sars->sample_from_day }}
                      @if($sars->sample_from_hour) {{ $sars->sample_from_hour }} @endif
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Sampling Date To</td>
                  <td class="p-2">
                    @if($sars->sample_to_year && $sars->sample_to_month && $sars->sample_to_day)
                      {{ $sars->sample_to_year }}-{{ $sars->sample_to_month }}-{{ $sars->sample_to_day }}
                      @if($sars->sample_to_hour) {{ $sars->sample_to_hour }} @endif
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Type of Sample</td>
                  <td class="p-2">{{ $sars->type_of_sample ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Type of Composite Sample</td>
                  <td class="p-2">{{ $sars->type_of_composite_sample ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Sample Interval</td>
                  <td class="p-2">{{ $sars->sample_interval ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Flow Total</td>
                  <td class="p-2">{{ $sars->flow_total ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Flow Minimum</td>
                  <td class="p-2">{{ $sars->flow_minimum ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Flow Maximum</td>
                  <td class="p-2">{{ $sars->flow_maximum ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Temperature</td>
                  <td class="p-2">{{ $sars->temperature ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">COD</td>
                  <td class="p-2">{{ $sars->cod ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Total N / NH4-N</td>
                  <td class="p-2">{{ $sars->total_n_nh4_n ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">TSS</td>
                  <td class="p-2">{{ $sars->tss ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Dry Weather Conditions</td>
                  <td class="p-2">{{ $sars->dry_weather_conditions ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Last Rain Event</td>
                  <td class="p-2">{{ $sars->last_rain_event ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Associated Phenotype</td>
                  <td class="p-2">{{ $sars->associated_phenotype ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Genetic Marker</td>
                  <td class="p-2">{{ $sars->genetic_marker ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Date of Sample Preparation</td>
                  <td class="p-2">{{ $sars->date_of_sample_preparation ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Storage of Sample</td>
                  <td class="p-2">{{ $sars->storage_of_sample ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Volume of Sample</td>
                  <td class="p-2">{{ $sars->volume_of_sample ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Internal Standard Used 1</td>
                  <td class="p-2">{{ $sars->internal_standard_used1 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Method Used for Sample Preparation</td>
                  <td class="p-2">{{ $sars->method_used_for_sample_preparation ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Date of RNA Extraction</td>
                  <td class="p-2">{{ $sars->date_of_rna_extraction ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Method Used for RNA Extraction</td>
                  <td class="p-2">{{ $sars->method_used_for_rna_extraction ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Internal Standard Used 2</td>
                  <td class="p-2">{{ $sars->internal_standard_used2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">RNA 1</td>
                  <td class="p-2">{{ $sars->rna1 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">RNA 2</td>
                  <td class="p-2">{{ $sars->rna2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Replicates 1</td>
                  <td class="p-2">{{ $sars->replicates1 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Analytical Method Type</td>
                  <td class="p-2">{{ $sars->analytical_method_type ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Analytical Method Type Other</td>
                  <td class="p-2">{{ $sars->analytical_method_type_other ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Date of Analysis</td>
                  <td class="p-2">{{ $sars->date_of_analysis ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">LoD 1</td>
                  <td class="p-2">{{ $sars->lod1 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">LoD 2</td>
                  <td class="p-2">{{ $sars->lod2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">LoQ 1</td>
                  <td class="p-2">{{ $sars->loq1 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">LoQ 2</td>
                  <td class="p-2">{{ $sars->loq2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Uncertainty of the Quantification</td>
                  <td class="p-2">{{ $sars->uncertainty_of_the_quantification ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Efficiency</td>
                  <td class="p-2">{{ $sars->efficiency ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">RNA 3</td>
                  <td class="p-2">{{ $sars->rna3 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Pos Control Used</td>
                  <td class="p-2">{{ $sars->pos_control_used ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Replicates 2</td>
                  <td class="p-2">{{ $sars->replicates2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Ct #</td>
                  <td class="p-2">{{ $sars->ct ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Gene 1</td>
                  <td class="p-2">{{ $sars->gene1 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Gene 2</td>
                  <td class="p-2">{{ $sars->gene2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Comment</td>
                  <td class="p-2">{{ $sars->comment ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Created At</td>
                  <td class="p-2">{{ $sars->created_at ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Updated At</td>
                  <td class="p-2">{{ $sars->updated_at ?? 'N/A' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
