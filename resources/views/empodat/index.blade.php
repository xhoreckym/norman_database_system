<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>


  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="recordsTable()" x-init="initLeaflet()">
          {{-- main div --}}

          <a
            href="{{ route('codsearch.filter', [
                'countrySearch' => $countrySearch,
                'matrixSearch' => $matrixSearch,
                'sourceSearch' => $sourceSearch,
                'year_from' => $year_from ?? '',
                'year_to' => $year_to ?? '',
                'displayOption' => $displayOption,
                'substances' => $substances,
                'categoriesSearch' => $categoriesSearch,
                'typeDataSourcesSearch' => $typeDataSourcesSearch,
                'concentrationIndicatorSearch' => $concentrationIndicatorSearch,
                'analyticalMethodSearch' => $analyticalMethodSearch,
                'dataSourceLaboratorySearch' => $dataSourceLaboratorySearch,
                'dataSourceOrganisationSearch' => $dataSourceOrganisationSearch,
                'qualityAnalyticalMethodsSearch' => $qualityAnalyticalMethodsSearch,
                'query_log_id' => $query_log_id,
            ]) }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </a>

          <div class="text-gray-600 flex border-l-2 border-white">
            @if ($displayOption == 1)
              {{-- use simple output --}}
              @livewire('empodat.query-counter', ['queryId' => $query_log_id, 'empodatsCount' => $empodatsCount, 'count_again' => request()->has('page') ? false : true])
            @else
              {{-- use advanced output --}}
              {{-- <span>Number of matched records: </span><span class="font-bold">&nbsp;{{number_format($empodats->total(), 0, " ", " ") ?? ''}}&nbsp;</span> <span> of {{number_format($empodatsCount, 0, " ", " ") }}</span>. --}}

              <div class="py-2">
                Number of matched records:
              </div>
              <div class="py-2 mx-1 font-bold">
                {{ number_format($empodats->total(), 0, '.', ' ') }}
              </div>

              <div class="py-2">
                of <span> {{ number_format($empodatsCount, 0, ' ', ' ') }}
                  @if (is_numeric($empodats->total()))
                    @if (($empodats->total() / $empodatsCount) * 100 < 0.01)
                      which is &le; 0.01% of total records.
                    @else
                      which is {{ number_format(($empodats->total() / $empodatsCount) * 100, 3, '.', ' ') }}% of total
                      records.
                    @endif
                  @endif
                </span>
              </div>

            @endif

            @auth
              <div class="py-2 px-2"><a href="{{ route('codsearch.download', ['query_log_id' => $query_log_id]) }}"
                  class="btn-download">Download</a></div>
            @else
              <div class="py-2 px-2 text-gray-400">Downloads are available for registered users only</div>
            @endauth


          </div>

          <div class="text-gray-600 flex border-l-2 border-white">
            Search parameters:&nbsp;<span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
                {{-- if value is array|collection then use for each, othervise display value --}}
                @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                  {{-- If $value is an array or collection, loop over each element --}}
                  @foreach ($value as $item)
                    {{ $item }}@if (!$loop->last)
                      ,
                    @endif
                  @endforeach
                @else
                  {{-- Otherwise, just display the single value --}}
                  {{ $value }}
                  @endif @if (!$loop->last)
                    ;
                  @endif
                @endforeach
            </span>
          </div>


          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Substance</th>
                <th>Concentration</th>
                <th>Ecosystem/Matrix</th>
                <th>Country</th>
                <th>Sampling year</th>
                <th>Sampling station</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($empodats as $e)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="">
                      {{ $e->id }}
                      <a href="{{ route('codsearch.show', $e->id) }}" class="link-lime-text"
                        x-on:click.prevent="openModal({{ $e->id }})">
                        <i class="fas fa-search"></i>
                      </a>
                    </div>
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->substance)
                      {{ $e->substance->name ?? 'N/A' }}
                    @else
                      N/A (ID: {{ $e->substance_id }})
                    @endif
                    @role('super_admin')
                      <span class="text-xss text-gray-500"> ({{ $e->substance_id }})</span>
                    @endrole
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->concentration_indicator_id == 0)
                      {{ $e->concentration_indicator_id }}
                    @elseif($e->concentration_indicator_id > 1)
                      @if ($e->concentrationIndicator)
                        {{ $e->concentrationIndicator->name ?? 'N/A' }}
                      @else
                        N/A
                      @endif
                    @else
                      <span
                        class="font-medium">{{ $e->concentration_value ?? 'N/A' }}</span>&nbsp;{{ $e->matrix ? $e->matrix->unit ?? '' : '' }}
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->matrix)
                      {{ $e->matrix->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station && $e->station->country && is_object($e->station->country))
                      {{ $e->station->country->name ?? 'N/A' }} - {{ $e->station->country->code ?? 'N/A' }}
                    @elseif($e->station && $e->station->country)
                      {{ $e->station->country ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->sampling_date_year }}
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station)
                      {{ $e->station->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          @if ($displayOption == 1)
            {{-- use simple output --}}

            <div class="flex justify-center space-x-4 mt-4">
              @if ($empodats->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $empodats->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($empodats->hasMorePages())
                <a href="{{ $empodats->nextPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Next
                </a>
              @else
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Next
                </span>
              @endif
            </div>
          @else
            {{-- use advanced output --}}
            {{ $empodats->links('pagination::tailwind') }}
          @endif



          <!-- The Modal (hidden by default) -->
          <div class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
            x-show="showModal"x-transition>
            <div class="bg-white w-11/12 md:w-2/3 lg:w-1/2 xl:w-1/3 rounded shadow-lg relative"
              x-trap.inert="showModal">
              <!-- Modal Header -->
              <div class="flex justify-between items-center border-b px-4 py-2">
                <div class="flex items-center space-x-4">
                  <h3 class="text-lg font-semibold">Record ID: <span x-text="recordId"></span></h3>
                  <h3 class="text-lg font-semibold text-gray-500">DCT Analysis ID: <span
                      x-text="record?.dct_analysis_id || 'N/A'"></span></h3>
                </div>
                <button @click="closeModal()" class="text-gray-500 hover:text-gray-700 text-xl">
                  &times;
                </button>
              </div>

              <!-- Modal Content -->
              <div class="p-4 max-h-[60vh] overflow-y-auto">

                <!-- Show details with Alpine binding -->
                {{-- <p><strong>Name:</strong> <span x-text="record?.name"></span></p> --}}

                <div class="">
                  <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Substance</div>
                  <div class="flex justify-between py-1 text-sm  bg-slate-100">
                    <!-- pair[0] = key, pair[1] = value -->
                    <div class="px-1 font-semibold">Substance</div>
                    <div class="px-1" x-text="record?.substance?.name || 'N/A'"></div>
                  </div>
                  <div class="flex justify-between py-1 text-sm bg-slate-200">
                    <!-- pair[0] = key, pair[1] = value -->
                    <div class="px-1 font-semibold">Code</div>
                    <!-- Dynamic link -->
                    <a :href="'{{ route('substances.show', ':id') }}'.replace(':id', record?.substance_id)"
                      target="_blank" class="link-lime-text px-1"
                      x-text="record?.substance?.prefixed_code || 'N/A'"></a>
                  </div>
                  <div class="flex justify-between py-1 text-sm bg-slate-100">
                    <!-- pair[0] = key, pair[1] = value -->
                    <div class="px-1 font-semibold">StdInChIKey</div>
                    <div class="px-1" x-text="record?.substance?.stdinchikey || 'N/A'"></div>
                  </div>
                  <div class="flex justify-between py-1 text-sm bg-slate-200">
                    <!-- pair[0] = key, pair[1] = value -->
                    <div class="px-1 font-semibold">CAS Number</div>
                    <div class="px-1" x-text="record?.substance?.cas_number || 'N/A'"></div>
                  </div>
                </div>

                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Concentration</div>
                <div class="flex justify-between py-1 text-sm bg-slate-100">
                  <div class="px-1 font-semibold">Concentration</div>
                  <div class="px-1">
                    <template x-if="record?.concentration_indicator_id == 0">
                      <span x-text="record?.concentration_indicator_id || 'N/A'"></span>
                    </template>
                    <template x-if="record?.concentration_indicator_id > 1">
                      <span x-text="record?.concentration_indicator?.name || 'N/A'"></span>
                    </template>
                    <template x-if="record?.concentration_indicator_id == 1">
                      <span><span class="font-medium" x-text="record?.concentration_value || 'N/A'"></span>&nbsp;<span
                          x-text="record?.matrix?.unit || ''"></span></span>
                    </template>
                  </div>
                </div>
                <div class="flex justify-between py-1 text-sm bg-slate-200">
                  <div class="px-1 font-semibold">Sampling Date</div>
                  <div class="px-1" x-text="record?.formatted_sampling_date || 'N/A'"></div>
                </div>

                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Analytical Method</div>
                <div class="flex justify-between py-1 text-sm bg-slate-100">
                  <div class="px-1 font-semibold">sample_preparation_method_other</div>
                  <div class="px-1">
                    <template
                      x-if="!record?.analytical_method?.sample_preparation_method_other || record?.analytical_method?.sample_preparation_method_other === null">
                      <span>N/A</span>
                    </template>
                    <template
                      x-if="record?.analytical_method?.sample_preparation_method_other && record?.analytical_method?.sample_preparation_method_other > 0">
                      <span x-text="record?.analytical_method?.samplePreparationMethodOther?.name || 'N/A'"></span>
                    </template>
                  </div>
                </div>
                <!-- We'll loop over stationArray -->
                <template x-for="(pair, index) in analyticalMethodArray" :key="index">
                  <!-- index = 0,1,2,... so we can do odd/even backgrounds -->
                  <div :class="index % 2 === 0 ? 'py-1 bg-slate-200' : 'py-1 bg-slate-100'">
                    <div class="flex justify-between py-1 text-sm">
                      <!-- pair[0] = key, pair[1] = value -->
                      <div class="px-1 font-semibold" x-text="pair[0]"></div>
                      <div class="px-1" x-text="pair[1]"></div>
                    </div>
                  </div>
                </template>

                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Station</div>
                <!-- We'll loop over stationArray -->
                <template x-for="(pair, index) in stationArray" :key="index">
                  <!-- index = 0,1,2,... so we can do odd/even backgrounds -->
                  <div :class="index % 2 === 0 ? 'py-1 bg-slate-100' : 'py-1 bg-slate-200'">
                    <div class="flex justify-between py-1 text-sm">
                      <!-- pair[0] = key, pair[1] = value -->
                      <div class="px-1 font-semibold" x-text="pair[0]"></div>
                      <div class="px-1" x-text="pair[1]"></div>
                    </div>
                  </div>
                </template>

                <!-- Leaflet map container -->
                <div id="map" class="mt-4 w-full h-64 bg-gray-200"></div>

                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Data Source</div>
                <!-- We'll loop over stationArray -->
                <template x-for="(pair, index) in dataSourceArray" :key="index">
                  <!-- index = 0,1,2,... so we can do odd/even backgrounds -->
                  <div :class="index % 2 === 0 ? 'py-1 bg-slate-100' : 'py-1 bg-slate-200'">
                    <div class="flex justify-between py-1 text-sm">
                      <!-- pair[0] = key, pair[1] = value -->
                      <div class="px-1 font-semibold" x-text="pair[0]"></div>
                      <div class="px-1" x-text="pair[1]"></div>
                    </div>
                  </div>
                </template>

                <!-- Debug section - remove this after testing -->
                <div class="font-semibold text-base border-b-2 border-red-500 text-center">Debug Info</div>
                <div class="py-1 bg-red-50 text-xs p-2">
                  <div>Has matrix_data: <span x-text="record?.matrix_data ? 'Yes' : 'No'"></span></div>
                  <div>Has meta_data: <span x-text="record?.matrix_data?.meta_data ? 'Yes' : 'No'"></span></div>
                  <div>Meta data keys: <span
                      x-text="record?.matrix_data?.meta_data ? Object.keys(record.matrix_data.meta_data).length : '0'"></span>
                  </div>
                  <div>Meta data preview: <span
                      x-text="record?.matrix_data?.meta_data ? JSON.stringify(record.matrix_data.meta_data).substring(0, 100) + '...' : 'None'"></span>
                  </div>
                  <div>metaDataArray length: <span x-text="metaDataArray ? metaDataArray.length : '0'"></span></div>
                  <div>metaDataArray preview: <span
                      x-text="metaDataArray && metaDataArray.length > 0 ? JSON.stringify(metaDataArray.slice(0, 3)) : 'Empty'"></span>
                  </div>
                </div>

                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Matrix Data</div>
                <!-- Debug: Show what's in the record object -->
                <div class="py-1 bg-red-50 text-xs p-2 mb-2">
                  <div class="font-bold">DEBUG - Record Object:</div>
                  <div>Has record: <span x-text="record ? 'Yes' : 'No'"></span></div>
                  <div>Record keys: <span x-text="record ? Object.keys(record) : 'No record'"></span></div>
                  <div>Has matrix_data: <span x-text="record?.matrix_data ? 'Yes' : 'No'"></span></div>
                  <div>matrix_data type: <span x-text="record?.matrix_data ? typeof record.matrix_data : 'N/A'"></span></div>
                  <div>matrix_data value: <span x-text="record?.matrix_data ? JSON.stringify(record.matrix_data) : 'N/A'"></span></div>
                </div>

                <template x-if="record?.matrix_data">
                  <div class="py-1 bg-slate-100">
                    <div class="flex justify-between py-1 text-sm">
                      <div class="px-1 font-semibold">Matrix Type</div>
                      <div class="px-1" x-text="record.matrix_data.type || 'N/A'"></div>
                    </div>
                  </div>
                  <div class="py-1 bg-slate-200">
                    <div class="flex justify-between py-1 text-sm">
                      <div class="px-1 font-semibold">Matrix Code AAA</div>
                      <div class="px-1" x-text="record.matrix_data.code || 'N/A'"></div>
                    </div>
                  </div>
                  <div class="py-1 bg-slate-100">
                    <div class="flex justify-between py-1 text-sm">
                      <div class="px-1 font-semibold">Meta Data AAA</div>
                      <div class="px-1">
                        <div class="text-xs bg-gray-100 p-2 rounded overflow-x-auto max-h-32 overflow-y-auto">
                          <div class="mb-2 font-bold">Raw metaDataArray:</div>
                          <div class="mb-2 text-red-600" x-text="JSON.stringify(metaDataArray)"></div>
                          
                          <div class="mb-2 font-bold">metaDataArray length:</div>
                          <div class="mb-2 text-blue-600" x-text="metaDataArray ? metaDataArray.length : 'undefined'"></div>
                          
                          <div class="mb-2 font-bold">metaDataArray type:</div>
                          <div class="mb-2 text-green-600" x-text="typeof metaDataArray"></div>
                          
                          <div class="mb-2 font-bold">metaDataArray keys:</div>
                          <div class="mb-2 text-purple-600" x-text="metaDataArray ? Object.keys(metaDataArray) : 'undefined'"></div>
                          
                          <div class="mb-2 font-bold">First 3 items:</div>
                          <template x-for="(item, index) in metaDataArray ? metaDataArray.slice(0, 3) : []" :key="index">
                            <div class="mb-1 border-b border-gray-200 pb-1">
                              <span class="font-medium text-gray-700">Item [x-text="index"]:</span>
                              <span class="ml-2 text-gray-600" x-text="JSON.stringify(item)"></span>
                            </div>
                          </template>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
                <!-- Always show matrix data section for debugging -->
                <div class="py-1 bg-slate-100">
                  <div class="flex justify-between py-1 text-sm">
                    <div class="px-1 font-semibold">Matrix Data (Always Visible)</div>
                    <div class="px-1">
                      <template x-if="record?.matrix_data">
                        <span class="text-green-600">✓ Has matrix_data</span>
                      </template>
                      <template x-if="!record?.matrix_data">
                        <span class="text-red-600">✗ No matrix_data</span>
                      </template>
                    </div>
                  </div>
                </div>

                <!-- Matrix Data Table -->
                <div class="py-1 bg-slate-100">
                  <div class="px-1">
                    <div class="font-semibold mb-2">Matrix Data Details</div>
                    
                    <!-- Basic Matrix Info -->
                    <div class="mb-3">
                      <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="bg-gray-50 p-2 rounded">
                          <span class="font-medium">Type:</span>
                          <span class="ml-2 text-gray-600" x-text="record?.matrix_data?.type || 'N/A'"></span>
                        </div>
                        <div class="bg-gray-50 p-2 rounded">
                          <span class="font-medium">Code:</span>
                          <span class="ml-2 text-gray-600" x-text="record?.matrix_data?.code || 'N/A'"></span>
                        </div>
                      </div>
                    </div>

                    <!-- Meta Data Table -->
                    <div class="mb-3">
                      <div class="font-medium mb-2">Meta Data:</div>
                      <div class="overflow-x-auto">
                        <table class="w-full text-xs border border-gray-300">
                          <thead>
                            <tr class="bg-gray-200">
                              <th class="border border-gray-300 px-2 py-1 text-left font-semibold">Field</th>
                              <th class="border border-gray-300 px-2 py-1 text-left font-semibold">Value</th>
                            </tr>
                          </thead>
                          <tbody>
                            <template x-for="(value, key) in record?.matrix_data?.meta_data || {}" :key="key">
                              <tr class="bg-white">
                                <td class="border border-gray-300 px-2 py-1 font-medium text-gray-700" x-text="key"></td>
                                <td class="border border-gray-300 px-2 py-1 text-gray-600">
                                  <template x-if="value === null">
                                    <span class="text-gray-400 italic">(null)</span>
                                  </template>
                                  <template x-if="value === ''">
                                    <span class="text-gray-400 italic">(empty)</span>
                                  </template>
                                  <template x-if="value !== null && value !== ''">
                                    <span x-text="value"></span>
                                  </template>
                                </td>
                              </tr>
                            </template>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

                <template x-if="!record?.matrix_data">
                  <div class="py-1 bg-slate-100">
                    <div class="flex justify-between py-1 text-sm">
                      <div class="px-1 font-semibold">Matrix Data</div>
                      <div class="px-1 text-gray-500">No matrix data available</div>
                    </div>
                  </div>
                </template>

              </div>

              <!-- Modal Footer -->
              <div class="flex justify-end border-t px-4 py-2">
                <button @click="closeModal()" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                  Close
                </button>
              </div>
            </div>

            @push('scripts')
              <script>
                // We can define a function that returns our Alpine state
                function recordsTable() {
                  return {
                    showModal: false,
                    record: null,
                    recordId: null,
                    mapInstance: null,
                    stationArray: [],
                    analyticalMethodArray: [],
                    dataSourceArray: [],
                    metaDataArray: [],

                    initLeaflet() {
                      // We'll initialize Leaflet once when component loads
                      // Leaflet CSS/JS should already be in your <head> or loaded in layout
                      // but you can also add them here if needed.

                      // We'll wait to set the view until after we have coordinates
                      // or we can set some default. For now, let's do a blank init.
                      this.mapInstance = L.map('map', {
                        center: [0, 0],
                        zoom: 2
                      });

                      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                      }).addTo(this.mapInstance);
                    },

                    async openModal(recordId) {
                      console.log('Opening modal for record ID:', recordId);
                      this.recordId = recordId; // Store the record ID

                      // Fetch record data from our /records/:id/json route
                      const response = await fetch(
                        "{{ route('codsearch.show', ':id') }}"
                        .replace(':id', recordId)
                      );

                      if (!response.ok) {
                        console.error('Failed to fetch record data:', response.status, response.statusText);
                        return;
                      }

                      this.record = await response.json();
                      console.log('Fetched record data:', this.record);
                      console.log('recordId from parameter:', this.recordId);
                      console.log('record.id from API response:', this.record?.id);
                      console.log('Are they the same?', this.recordId === this.record?.id);



                      // Build an array of station entries, skipping unwanted keys and empty/null values
                      if (this.record.station) {
                        const excludedKeys = ['id', 'created_at', 'updated_at'];
                        this.stationArray = Object.entries(this.record.station)
                          .filter(([key, val]) =>
                            !excludedKeys.includes(key) &&
                            val !== null &&
                            val !== ''
                          );
                      } else {
                        this.stationArray = [];
                      }

                      // Build an array of analyticalMethod entries, skipping unwanted keys and empty/null values
                      // console.log(this.record);
                      if (this.record.analytical_method) {
                        const excludedKeys = ['id', 'created_at', 'updated_at'];
                        this.analyticalMethodArray = Object.entries(this.record.analytical_method)
                          .filter(([key, val]) =>
                            !excludedKeys.includes(key) &&
                            val !== null &&
                            val !== ''
                          );
                      } else {
                        this.analyticalMethodArray = [];
                      }

                      // Build an array of dataSource entries, skipping unwanted keys and empty/null values
                      if (this.record.data_source) {
                        const excludedKeys = ['id', 'created_at', 'updated_at'];
                        this.dataSourceArray = Object.entries(this.record.data_source)
                          .filter(([key, val]) =>
                            !excludedKeys.includes(key) &&
                            val !== null &&
                            val !== ''
                          );
                      } else {
                        this.dataSourceArray = [];
                      }

                                             // Build an array of meta_data entries, skipping unwanted keys and empty/null values
                       console.log('=== META DATA PROCESSING DEBUG ===');
                       console.log('this.record:', this.record);
                       console.log('this.record.matrix_data:', this.record.matrix_data);
                       console.log('this.record.matrix_data?.meta_data:', this.record.matrix_data?.meta_data);
                       
                       if (this.record.matrix_data && this.record.matrix_data.meta_data) {
                         const excludedKeys = ['id', 'created_at', 'updated_at'];
                         console.log('Processing meta_data, excludedKeys:', excludedKeys);
                         
                         this.metaDataArray = Object.entries(this.record.matrix_data.meta_data)
                           .filter(([key, val]) =>
                             !excludedKeys.includes(key) &&
                             val !== null &&
                             val !== ''
                           );
                         
                         console.log('Created metaDataArray:', this.metaDataArray);
                         console.log('metaDataArray length:', this.metaDataArray.length);
                         console.log('metaDataArray type:', typeof this.metaDataArray);
                         console.log('metaDataArray isArray:', Array.isArray(this.metaDataArray));
                       } else {
                         this.metaDataArray = [];
                         console.log('No meta_data found, metaDataArray set to empty');
                         console.log('matrix_data exists:', !!this.record.matrix_data);
                         console.log('meta_data exists:', !!this.record.matrix_data?.meta_data);
                       }
                       console.log('=== END META DATA PROCESSING DEBUG ===');

                      // Show the modal
                      this.showModal = true;

                      // Now that we have record coordinates, e.g. this.record.station.latitude, this.record.station.longitude,
                      // update the map. We'll assume lat/longitude exist on the record.
                      if (this.record.station.latitude && this.record.station.longitude) {
                        // Fly or setView to the record's location
                        this.mapInstance.setView([this.record.station.latitude, this.record.station.longitude], 7);

                        // Clear existing markers (if any).
                        // We'll do a simple approach each time:
                        this.mapInstance.eachLayer((layer) => {
                          if (layer instanceof L.Marker) {
                            this.mapInstance.removeLayer(layer);
                          }
                        });

                        // Add a marker
                        L.marker([this.record.station.latitude, this.record.station.longitude])
                          .addTo(this.mapInstance)
                          .bindPopup(`Record ID: ${this.recordId}`);
                      }
                    },

                    closeModal() {
                      this.showModal = false;
                      this.record = null;
                      this.recordId = null;
                      // Optionally reset map or let it persist
                    }
                  }
                }
              </script>
            @endpush

            {{-- end of main div --}}
          </div>
        </div>
      </div>
    </div>

</x-app-layout>
