<x-app-layout>
  <x-slot name="header">
    @include('arbg.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Notification Banner --}}
          <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500 text-lg"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-blue-700">
                  <strong>Note:</strong> This record is displayed in a new browser tab.
                  To return to your search results, please switch to the previous tab instead of using the "Search" link in the navigation bar. You may use keystroke <span class="font-mono">CTRL+SHIFT+TAB</span>.
                </p>
              </div>
            </div>
          </div>

          {{-- Record Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">ARB Record at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              @if ($record->id)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->id }}</p>
                </div>
              @endif
              @if ($record->sampleMatrix)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sample Matrix</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->sampleMatrix->name }}</p>
                </div>
              @elseif ($record->sample_matrix_other)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sample Matrix</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->sample_matrix_other }}</p>
                </div>
              @endif
              @if ($record->bacterialGroup)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Bacterial Group</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->bacterialGroup->name }}</p>
                </div>
              @elseif ($record->bacterial_group_other)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Bacterial Group</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->bacterial_group_other }}</p>
                </div>
              @endif
              @if ($record->abundance)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Abundance [CFUs/ml]</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->abundance }}</p>
                </div>
              @endif
              @if ($record->sampling_date)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sampling Date</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->sampling_date }}</p>
                </div>
              @endif
              @if ($record->coordinate && $record->coordinate->station_name)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sampling Station</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->coordinate->station_name }}</p>
                </div>
              @endif
              @if ($record->coordinate && $record->coordinate->country)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Country</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->coordinate->country->name }}</p>
                </div>
              @elseif ($record->coordinate && $record->coordinate->country_id)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Country</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->coordinate->country_id }}</p>
                </div>
              @endif
              @if ($record->source && $record->source->organisation)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Organisation</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->source->organisation }}</p>
                </div>
              @endif
            </div>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full overflow-x-auto">
            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4" style="table-layout: fixed;">
              @php
                $rowIndex = 0;
                $excludedKeys = ['coordinate', 'sample_matrix', 'bacterial_group', 'source', 'concentration_data', 'grain_size_distribution', 'soil_texture', 'soil_type', 'depth_sampling_type', 'method', 'created_at', 'updated_at'];
              @endphp

              @foreach ($record->toArray() as $key => $value)
                {{-- Skip relationships and system fields --}}
                @if (in_array($key, $excludedKeys))
                  @continue
                @endif

                {{-- Skip ID fields except the main record ID --}}
                @if (str_ends_with($key, '_id') && $key !== 'id')
                  @continue
                @endif

                {{-- Skip null values and empty arrays --}}
                @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                  @continue
                @endif

                <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                  <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                    @if (is_array($value))
                      {{ json_encode($value) }}
                    @else
                      {{ $value }}
                    @endif
                  </td>
                </tr>
                @php $rowIndex++; @endphp
              @endforeach

              {{-- Coordinate/Station Information --}}
              @if ($record->coordinate)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Station / Coordinate Information</td>
                </tr>

                {{-- Show coordinates with map link if available --}}
                @if ($record->coordinate->latitude_decimal && $record->coordinate->longitude_decimal)
                  <tr class="bg-emerald-100">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px;">Coordinates</td>
                    <td class="p-1" style="width: 80%;">
                      <a href="https://www.google.com/maps?q={{ $record->coordinate->latitude_decimal }},{{ $record->coordinate->longitude_decimal }}"
                         target="_blank"
                         class="text-teal-700 hover:text-teal-900 hover:underline">
                        {{ $record->coordinate->latitude_decimal }}, {{ $record->coordinate->longitude_decimal }}
                        <i class="fas fa-external-link-alt text-xs ml-1"></i>
                      </a>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2" class="p-0">
                      <div id="station-map" class="w-full h-64 rounded-b-lg border border-gray-300"></div>
                    </td>
                  </tr>
                @endif

                @php $rowIndex = 0; @endphp
                @foreach ($record->coordinate->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'latitude_decimal', 'longitude_decimal', 'created_at', 'updated_at', 'country']))
                    @continue
                  @endif

                  @if (is_null($value) || (is_array($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif

                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Sample Matrix Information --}}
              @if ($record->sampleMatrix)
                <tr class="bg-teal-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Sample Matrix</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->sampleMatrix->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-teal-50 @else bg-teal-100 @endif">
                    <td class="p-1 font-bold text-teal-900" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1 text-teal-800" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Bacterial Group Information --}}
              @if ($record->bacterialGroup)
                <tr class="bg-teal-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Bacterial Group</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->bacterialGroup->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-teal-50 @else bg-teal-100 @endif">
                    <td class="p-1 font-bold text-teal-900" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1 text-teal-800" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Source/Organisation Information --}}
              @if ($record->source)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Data Source</td>
                </tr>
                @php $rowIndex = 0; @endphp

                {{-- Type of Data Source --}}
                @if ($record->source->typeOfDataSource)
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">Type of data source</td>
                    <td class="p-1" style="width: 80%;">{{ $record->source->typeOfDataSource->name }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endif

                {{-- Type of Monitoring --}}
                @if ($record->source->typeOfMonitoring)
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">Type of monitoring</td>
                    <td class="p-1" style="width: 80%;">{{ $record->source->typeOfMonitoring->name }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endif

                @foreach ($record->source->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'source_id', 'type_of_data_source_id', 'type_of_monitoring_id', 'type_of_data_source', 'type_of_monitoring', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_array($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Method Information --}}
              @if ($record->method)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Analytical Method</td>
                </tr>
                @php $rowIndex = 0; @endphp

                {{-- Limit of Detection --}}
                @if ($record->method->lod)
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">Limit of Detection (LoD):<br><span class="font-normal text-gray-500">[{{ $record->method->lod_unit ?? 'CFU/ml' }}]</span></td>
                    <td class="p-1" style="width: 80%;">{{ $record->method->lod }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endif

                {{-- Limit of Quantification --}}
                @if ($record->method->loq)
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">Limit of Quantification (LoQ):<br><span class="font-normal text-gray-500">[{{ $record->method->loq_unit ?? 'CFU/ml' }}]</span></td>
                    <td class="p-1" style="width: 80%;">{{ $record->method->loq }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endif

                {{-- Bacteria isolation method --}}
                @if ($record->method->bacteriaIsolationMethod)
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">Bacteria isolation method:</td>
                    <td class="p-1" style="width: 80%;">{{ $record->method->bacteriaIsolationMethod->name }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endif

                {{-- Phenotype determination method --}}
                @if ($record->method->phenotypeDeterminationMethod)
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">Phenotype determination method:</td>
                    <td class="p-1" style="width: 80%;">{{ $record->method->phenotypeDeterminationMethod->name }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endif

                {{-- Interpretation criteria --}}
                @if ($record->method->interpretationCriteria)
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">Interpretation criteria:</td>
                    <td class="p-1" style="width: 80%;">{{ $record->method->interpretationCriteria->name }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endif
              @endif

              {{-- Soil Type Information --}}
              @if ($record->soilType)
                <tr class="bg-amber-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Soil Type</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->soilType->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'soil_type_id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-amber-50 @else bg-amber-100 @endif">
                    <td class="p-1 font-bold text-amber-900" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1 text-amber-800" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Soil Texture Information --}}
              @if ($record->soilTexture)
                <tr class="bg-amber-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Soil Texture</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->soilTexture->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'soil_texture_id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-amber-50 @else bg-amber-100 @endif">
                    <td class="p-1 font-bold text-amber-900" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1 text-amber-800" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Depth Sampling Type Information --}}
              @if ($record->depthSamplingType)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Depth Sampling Type</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->depthSamplingType->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Grain Size Distribution Information --}}
              @if ($record->grainSizeDistribution)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Grain Size Distribution</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->grainSizeDistribution->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'grain_size_distribution_id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Concentration Data Information --}}
              @if ($record->concentrationData)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Concentration Data</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->concentrationData->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'concentration_data_id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Initialize Leaflet map if coordinates are available --}}
  @if ($record->coordinate && $record->coordinate->latitude_decimal && $record->coordinate->longitude_decimal)
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $record->coordinate->latitude_decimal }};
        const lng = {{ $record->coordinate->longitude_decimal }};
        const stationName = @json($record->coordinate->station_name ?? 'Station');

        // Initialize the map
        const map = L.map('station-map').setView([lat, lng], 10);

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add marker with popup
        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(
          '<strong>' + stationName + '</strong>' +
          '<br><small>' + lat.toFixed(6) + ', ' + lng.toFixed(6) + '</small>'
        ).openPopup();

        // Fix for map not rendering correctly in hidden/dynamic containers
        setTimeout(function() {
          map.invalidateSize();
        }, 100);
      });
    </script>
  @endif

</x-app-layout>
