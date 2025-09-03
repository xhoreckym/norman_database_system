<x-app-layout>
  <x-slot name="header">
    @include('passive.header')
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
            <h3 class="text-lg font-semibold text-gray-800">Record ID: {{ $passive->id }}</h3>
            <div class="flex space-x-2">
              @if (auth()->check() && 
                   (auth()->user()->hasRole('super_admin') || 
                    auth()->user()->hasRole('admin') || 
                    auth()->user()->hasRole('passive')))
                <a href="{{ route('passive.search.edit', ['search' => $passive->id] + $request->except(['search'])) }}" class="link-edit">
                  <i class="fas fa-edit mr-1"></i> Edit
                </a>
              @endif
              <a href="{{ route('passive.search.search', $request->except(['search'])) }}" class="link-lime">
                <i class="fas fa-arrow-left mr-1"></i> Back to Search
              </a>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="table-standard">
              <tbody>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Substance ID</td>
                  <td class="p-2">{{ $passive->sus_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Substance</td>
                  <td class="p-2">{{ $passive->substance->name ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Country</td>
                  <td class="p-2">{{ $passive->country->name ?? $passive->country_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Country Other</td>
                  <td class="p-2">{{ $passive->country_other ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Station Name</td>
                  <td class="p-2">{{ $passive->station_name ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Short Sample Code</td>
                  <td class="p-2">{{ $passive->short_sample_code ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Sample Code</td>
                  <td class="p-2">{{ $passive->sample_code ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Provider Code</td>
                  <td class="p-2">{{ $passive->provider_code ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">National Code</td>
                  <td class="p-2">{{ $passive->national_code ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">EC Code (WISE)</td>
                  <td class="p-2">{{ $passive->code_ec_wise ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">EC Code (Other)</td>
                  <td class="p-2">{{ $passive->code_ec_other ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Other Code</td>
                  <td class="p-2">{{ $passive->code_other ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Specific Locations</td>
                  <td class="p-2">{{ $passive->specific_locations ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Longitude</td>
                  <td class="p-2">{{ $passive->longitude_decimal ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Latitude</td>
                  <td class="p-2">{{ $passive->latitude_decimal ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Precision Coordinates</td>
                  <td class="p-2">{{ $passive->dpc_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Altitude</td>
                  <td class="p-2">{{ $passive->altitude ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Proxy Pressures</td>
                  <td class="p-2">{{ $passive->dpr_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Proxy Pressures Other</td>
                  <td class="p-2">{{ $passive->dpr_other ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Dynamic Sampling Stretch</td>
                  <td class="p-2">{{ $passive->ds_passive_sampling_stretch ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Stretch Start and End</td>
                  <td class="p-2">{{ $passive->ds_stretch_start_and_end ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">DS Longitude Start</td>
                  <td class="p-2">{{ $passive->ds_longitude_start_point_decimal ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">DS Latitude Start</td>
                  <td class="p-2">{{ $passive->ds_latitude_start_point_decimal ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">DS Longitude End</td>
                  <td class="p-2">{{ $passive->ds_longitude_end_point_decimal ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">DS Latitude End</td>
                  <td class="p-2">{{ $passive->ds_latitude_end_point_decimal ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">DS Precision Coordinates</td>
                  <td class="p-2">{{ $passive->ds_dpc_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">DS Altitude</td>
                  <td class="p-2">{{ $passive->ds_altitude ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">DS Proxy Pressures</td>
                  <td class="p-2">{{ $passive->ds_dpr_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">DS Proxy Pressures Other</td>
                  <td class="p-2">{{ $passive->ds_dpr_other ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Matrix</td>
                  <td class="p-2">{{ $passive->matrix->name ?? $passive->matrix_other ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Type Sampling</td>
                  <td class="p-2">{{ $passive->type_sampling_other ?? $passive->type_sampling_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Passive Sampler</td>
                  <td class="p-2">{{ $passive->passive_sampler_other ?? $passive->passive_sampler_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Sampler Type</td>
                  <td class="p-2">{{ $passive->sampler_type_other ?? $passive->sampler_type_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Sampler Mass</td>
                  <td class="p-2">{{ $passive->sampler_mass ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Sampler Surface Area</td>
                  <td class="p-2">{{ $passive->sampler_surface_area ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Sampling Date</td>
                  <td class="p-2">
                    @if($passive->date_sampling_start_year && $passive->date_sampling_start_month && $passive->date_sampling_start_day)
                      {{ $passive->date_sampling_start_year }}-{{ sprintf('%02d', $passive->date_sampling_start_month) }}-{{ sprintf('%02d', $passive->date_sampling_start_day) }}
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Exposure Time (Days)</td>
                  <td class="p-2">{{ $passive->exposure_time_days ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Exposure Time (Hours)</td>
                  <td class="p-2">{{ $passive->exposure_time_hours ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Date of Analysis</td>
                  <td class="p-2">{{ $passive->date_of_analysis ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Time of Analysis</td>
                  <td class="p-2">{{ $passive->time_of_analysis ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Name</td>
                  <td class="p-2">{{ $passive->name ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Basin Name</td>
                  <td class="p-2">{{ $passive->basin_name_other ?? $passive->basin_name_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Type Data Source</td>
                  <td class="p-2">{{ $passive->dts_other ?? $passive->dts_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Type Monitoring</td>
                  <td class="p-2">{{ $passive->dtm_other ?? $passive->dtm_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Concentration</td>
                  <td class="p-2">{{ $passive->dic_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Concentration Value</td>
                  <td class="p-2">{{ $passive->concentration_value ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Unit</td>
                  <td class="p-2">{{ $passive->unit ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Title of Project</td>
                  <td class="p-2">{{ $passive->title_of_project ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">pH</td>
                  <td class="p-2">{{ $passive->ph ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Temperature</td>
                  <td class="p-2">{{ $passive->temperature ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">SPM Concentration</td>
                  <td class="p-2">{{ $passive->spm_conc ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Salinity</td>
                  <td class="p-2">{{ $passive->salinity ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">DOC</td>
                  <td class="p-2">{{ $passive->doc ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Hardness</td>
                  <td class="p-2">{{ $passive->hardness ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">O2 1</td>
                  <td class="p-2">{{ $passive->o2_1 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">O2 2</td>
                  <td class="p-2">{{ $passive->o2_2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">BOD5</td>
                  <td class="p-2">{{ $passive->bod5 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">H2S</td>
                  <td class="p-2">{{ $passive->h2s ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">P-PO4</td>
                  <td class="p-2">{{ $passive->p_po4 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">N-NO2</td>
                  <td class="p-2">{{ $passive->n_no2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">TSS</td>
                  <td class="p-2">{{ $passive->tss ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">P Total</td>
                  <td class="p-2">{{ $passive->p_total ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">N-NO3</td>
                  <td class="p-2">{{ $passive->n_no3 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">N Total</td>
                  <td class="p-2">{{ $passive->n_total ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Remark 1</td>
                  <td class="p-2">{{ $passive->remark_1 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Remark 2</td>
                  <td class="p-2">{{ $passive->remark_2 ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Original Compound</td>
                  <td class="p-2">{{ $passive->orig_compound ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Original CAS No</td>
                  <td class="p-2">{{ $passive->orig_cas_no ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">P Determinand ID</td>
                  <td class="p-2">{{ $passive->p_determinand_id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">P/A Exposure Time</td>
                  <td class="p-2">{{ $passive->p_a_exposure_time ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">P/A Cruise Dates</td>
                  <td class="p-2">{{ $passive->p_a_cruise_dates ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">P/A River KM</td>
                  <td class="p-2">{{ $passive->p_a_river_km ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">P/A Sampler Sheets/Disks Nr</td>
                  <td class="p-2">{{ $passive->p_a_sampler_sheets_disks_nr ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">P/A Sample Code</td>
                  <td class="p-2">{{ $passive->p_a_sample_code ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-2 font-semibold">Created At</td>
                  <td class="p-2">{{ $passive->created_at ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-2 font-semibold">Updated At</td>
                  <td class="p-2">{{ $passive->updated_at ?? 'N/A' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
