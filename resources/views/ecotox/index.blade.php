<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="{ 
          showModal: false, 
          record: null,
          recordId: null,
          viewMode: 'table',
          async openModal(recordId) {
            try {
              console.log('Opening modal for recordId:', recordId);
              this.recordId = recordId;
              this.showModal = true;
              this.record = null;
              console.log('Modal state after opening:', { showModal: this.showModal, recordId: this.recordId });
              
              const url = '{{ route('ecotox.show', ':id') }}'.replace(':id', recordId);
              const response = await fetch(url);
              
              if (!response.ok) {
                throw new Error('Failed to fetch record data');
              }
              
              this.record = await response.json();
              console.log('Ecotox record data:', this.record);
              console.log('Modal state after loading data:', { showModal: this.showModal, recordId: this.recordId, hasRecord: !!this.record });
            } catch (error) {
              console.error('Error opening modal:', error);
              alert('Failed to load record data. Please try again.');
              this.closeModal();
            }
          },
          closeModal() {
            console.log('Closing modal, current state:', { showModal: this.showModal, recordId: this.recordId, hasRecord: !!this.record });
            this.showModal = false;
            this.record = null;
            this.recordId = null;
            console.log('Modal state after closing:', { showModal: this.showModal, recordId: this.recordId, hasRecord: !!this.record });
          }
        }">
          {{-- main div --}}
          
          <a href="{{ route('ecotox.search.filter', [
            'substances' => $request->input('substances'),
            'query_log_id' => $query_log_id
          ]) }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </a>
          
          <div class="text-gray-600 flex border-l-2 border-white">
            @if(isset($request->displayOption) && $request->displayOption == 1)
              {{-- Simple output --}}
              @livewire('backend.query-counter', [
                'queryId' => $query_log_id ?? null, 
                'resultsCount' => $resultsObjectsCount, 
                'count_again' => request()->has('page') ? false : true
              ])
            @else
              {{-- Advanced output with better styling --}}
              <div class="flex flex-wrap items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-center mr-4">
                  <span class="text-gray-700">Number of matched records:</span>
                  <span class="font-bold text-lg ml-2 text-sky-700">
                    {{ number_format($resultsObjects->total(), 0, ".", " ") }}
                  </span>
                </div>
                
                <div class="flex items-center">
                  <span class="text-gray-700">of</span>
                  <span class="font-medium ml-2 text-gray-800">
                    {{ number_format($resultsObjectsCount, 0, ".", " ") }}
                  </span>
                  
                  @if(is_numeric($resultsObjects->total()) && $resultsObjectsCount > 0)
                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                      @if($resultsObjects->total()/$resultsObjectsCount*100 < 0.01)
                        &le; 0.01% of total
                      @else
                        {{ number_format($resultsObjects->total()/$resultsObjectsCount*100, 2, ".", " ") }}% of total
                      @endif
                    </span>
                  @endif
                </div>
              </div>
            @endif
            
            @auth
              {{-- <div class="py-2 px-2"><a href="{{ route('ecotox.search.download', ['query_log_id' => $query_log_id]) }}" class="btn-download">Download</a></div> --}}
            @else
              <div class="py-2 px-2 text-gray-400">Downloads are available for registered users only</div>
            @endauth
          </div>
          
          <div class="text-gray-600 flex border-l-2 border-white">
            Search parameters:&nbsp;<span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
                @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                  @foreach ($value as $item)
                    {{ $item }}@if(!$loop->last), @endif
                  @endforeach
                @else
                  {{ $value }}
                @endif @if(!$loop->last); @endif
              @endforeach
            </span>
          </div>
          
          {{-- Tabs for different matrix-habitat/acute-chronic combinations --}}
          <div class="mt-4">
            <div class="border-b border-gray-200">
              <nav class="-mb-px flex space-x-8">
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-sky-500 text-sky-600" 
                        data-tab="all" 
                        data-filter-matrix="" 
                        data-filter-acute="">
                  All Results 
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-sky-100 text-sky-800 rounded-full">
                    {{ $resultsObjects->total() }}
                  </span>
                </button>
                
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" 
                        data-tab="freshwater-acute" 
                        data-filter-matrix="freshwater" 
                        data-filter-acute="acute">
                  Freshwater - Acute 
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'freshwater')->where('acute_or_chronic', 'acute')->count() }}
                  </span>
                </button>
                
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" 
                        data-tab="freshwater-chronic" 
                        data-filter-matrix="freshwater" 
                        data-filter-acute="chronic">
                  Freshwater - Chronic 
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'freshwater')->where('acute_or_chronic', 'chronic')->count() }}
                  </span>
                </button>
                
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" 
                        data-tab="marine-acute" 
                        data-filter-matrix="marine water" 
                        data-filter-acute="acute">
                  Marine Water - Acute 
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'marine water')->where('acute_or_chronic', 'acute')->count() }}
                  </span>
                </button>
                
                <button class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" 
                        data-tab="marine-chronic" 
                        data-filter-matrix="marine water" 
                        data-filter-acute="chronic">
                  Marine Water - Chronic 
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'marine water')->where('acute_or_chronic', 'chronic')->count() }}
                  </span>
                </button>
              </nav>
            </div>
          </div>
          
          {{-- Single Unified Table --}}
          <div class="mt-4">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th>Biotest ID</th>
                  <th>Taxonomic group</th>
                  <th>Scientific name</th>
                  <th>Endpoint</th>
                  <th>Duration</th>
                  <th>Effect measurement</th>
                  <th>Test type</th>
                  <th>Standard test</th>
                  <th>Effect based on</th>
                  <th>pH</th>
                  <th>Exposure regime</th>
                  <th>Purity [%]</th>
                  <th></th>
                  <th>Effect value [µg/L]</th>
                  <th>Measured or nominal</th>
                  <th>Reference</th>
                  @if(auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('ecotox')))
                    <th>Reliability score</th>
                    <th>Use of study</th>
                    <th>Editor</th>
                  @endif
                </tr>
              </thead>
              <tbody id="ecotox-table-body">
                @foreach ($resultsObjects as $e)
                  <tr class="ecotox-row @if($loop->odd) bg-slate-100 @else bg-slate-200 @endif" 
                      data-matrix="{{ $e->matrix_habitat }}" 
                      data-acute="{{ $e->acute_or_chronic }}">
                    <td class="p-1 text-center">
                      @if(auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('ecotox')))
                        <div class="">
                          
                          <a href="#" class="link-lime-text" title="Click to view details" 
                          x-on:click.prevent="openModal('{{ $e->ecotox_id }}')">
                            {{-- <i class="fas fa-search"></i> --}}
                            {{ $e->ecotox_id ?? 'N/A' }}
                          </a>
                        </div>
                      @else
                        {{ $e->ecotox_id ?? 'N/A' }}
                      @endif
                    </td>
                    <td class="p-1 text-center">{{ $e->taxonomic_group ?? 'N/A' }}</td>
                    <td class="p-1">
                      <div class="italic">{{ $e->scientific_name ?? 'N/A' }}</div>
                      @if($e->common_name)
                        <div class="text-xs text-gray-500">{{ $e->common_name }}</div>
                      @endif
                    </td>
                    <td class="p-1 text-center">{{ $e->endpoint ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->duration ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->effect_measurement ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->test_type ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->standard_test ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->effect ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->ph ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->exposure_regime ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->purity ?? 'N/A' }}</td>
                    <td class="p-1 text-center"></td>
                    <td class="p-1 text-center">
                      @if($e->concentration_value)
                        <div>{{ $e->concentration_qualifier ?? '' }} {{ number_format($e->concentration_value, 4) }}</div>
                        @if($e->unit_concentration)
                          <div class="text-xs text-gray-500">{{ $e->unit_concentration }}</div>
                        @endif
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="p-1 text-center">{{ $e->measured_or_nominal ?? 'N/A' }}</td>
                    <td class="p-1 text-center">
                      @if($e->bibliographic_source)
                        <div>{{ $e->bibliographic_source }}</div>
                        @if($e->year_publication)
                          <div class="text-xs text-gray-500">{{ $e->year_publication }}</div>
                        @endif
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    @if(auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('ecotox')))
                      <td class="p-1 text-center">{{ $e->reliability_study ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $e->use_study ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        @if($e->editorUser)
                          {{ $e->editorUser->name ?? 'N/A' }}
                        @else
                          N/A
                        @endif
                      </td>
                    @endif
                  </tr>
                @endforeach
              </tbody>
            </table>
            
            {{-- No results message (hidden by default) --}}
            <div id="no-results-message" class="hidden text-center py-8 text-gray-500">
              No results found for the selected filter.
            </div>
          </div>
          
          @if(isset($request->displayOption) && $request->displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($resultsObjects->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $resultsObjects->previousPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif
              
              @if ($resultsObjects->hasMorePages())
                <a href="{{ $resultsObjects->nextPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
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
            {{$resultsObjects->links('pagination::tailwind')}}
          @endif
          
          <!-- Modal Window -->
          <div x-show="showModal"
          x-cloak
          @keydown.escape.window="closeModal()"
          class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">
            
            <div class="bg-white w-11/12 md:w-3/4 lg:w-3/4 xl:w-2/3 rounded shadow-lg relative" 
                 @click.outside="closeModal()"
                 x-transition>
              
              <!-- Modal Header -->
              <div class="flex justify-between items-center border-b px-4 py-2 bg-lime-600 text-white">
                <div class="flex items-center space-x-4">
                  <h3 class="text-lg font-semibold">Ecotox Record ID: <span x-text="recordId"></span></h3>
                  <h3 class="text-lg font-semibold text-lime-200">Biotest ID: <span x-text="record?.ecotox_id || 'N/A'"></span></h3>
                </div>
                <button @click="closeModal()" class="text-white hover:text-gray-200 text-xl">
                  &times;
                </button>
              </div>
              
              <!-- Modal Content -->
              <div class="p-4 max-h-[70vh] overflow-y-auto">
                <div x-show="!record" class="text-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-lime-600 mx-auto"></div>
                  <p class="mt-2 text-gray-600">Loading record data...</p>
                </div>
                
                <div x-show="record" x-transition>
                  <!-- View Toggle Buttons -->
                  <div class="mb-4 flex space-x-2">
                    <button @click="viewMode = 'table'" 
                            :class="viewMode === 'table' ? 'bg-sky-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-2 rounded text-sm font-medium transition-colors">
                      Table View
                    </button>
                    <button @click="viewMode = 'detailed'" 
                            :class="viewMode === 'detailed' ? 'bg-sky-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-2 rounded text-sm font-medium transition-colors">
                      Detailed View
                    </button>
                  </div>

                  <!-- Table View -->
                  <div x-show="viewMode === 'table'">
                    <div class="overflow-x-auto">
                      <table class="w-full border border-gray-300 text-sm">
                        <thead>
                          <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Parameter Name</th>
                            <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Original</th>
                            <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Harmonised</th>
                            <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Final</th>
                          </tr>
                        </thead>
                        <tbody>
                          <!-- Substance Information -->
                          <tr class="bg-gray-50">
                            <td colspan="4" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">Substance Information</td>
                          </tr>
                          <tr>
                            <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Substance Name</td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.name || 'N/A'"></td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.name || 'N/A'"></td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.name || 'N/A'"></td>
                          </tr>
                          <tr class="bg-gray-50">
                            <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">CAS Number</td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.cas_number || 'N/A'"></td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.cas_number || 'N/A'"></td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.cas_number || 'N/A'"></td>
                          </tr>
                          <tr>
                            <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Substance Code</td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.prefixed_code || 'N/A'"></td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.prefixed_code || 'N/A'"></td>
                            <td class="border border-gray-300 px-3 py-2" x-text="record?.substance?.prefixed_code || 'N/A'"></td>
                          </tr>

                          <!-- Source Information -->
                          <tr class="bg-gray-50">
                            <td colspan="4" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">Source Information</td>
                          </tr>
                          <template x-for="(value, key) in record?.table_data?.Source || {}" :key="key">
                            <tr>
                              <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700" x-text="key"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.original || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.harmonised || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.final || 'N/A'"></td>
                            </tr>
                          </template>

                          <!-- Reference Information -->
                          <tr class="bg-gray-50">
                            <td colspan="4" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">Reference Information</td>
                          </tr>
                          <template x-for="(value, key) in record?.table_data?.Reference || {}" :key="key">
                            <tr>
                              <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700" x-text="key"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.original || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.harmonised || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.final || 'N/A'"></td>
                            </tr>
                          </template>

                          <!-- Test Information -->
                          <tr class="bg-gray-50">
                            <td colspan="4" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">Test Information</td>
                          </tr>
                          <template x-for="(value, key) in record?.table_data?.Test || {}" :key="key">
                            <tr>
                              <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700" x-text="key"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.original || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.harmonised || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.final || 'N/A'"></td>
                            </tr>
                          </template>

                          <!-- Organism Information -->
                          <tr class="bg-gray-50">
                            <td colspan="4" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">Organism Information</td>
                          </tr>
                          <template x-for="(value, key) in record?.table_data?.Organism || {}" :key="key">
                            <tr>
                              <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700" x-text="key"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.original || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.harmonised || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.final || 'N/A'"></td>
                            </tr>
                          </template>

                          <!-- Concentration Information -->
                          <tr class="bg-gray-50">
                            <td colspan="4" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">Concentration Information</td>
                          </tr>
                          <template x-for="(value, key) in record?.table_data?.Concentration || {}" :key="key">
                            <tr>
                              <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700" x-text="key"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.original || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.harmonised || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.final || 'N/A'"></td>
                            </tr>
                          </template>

                          <!-- Additional Information -->
                          <tr class="bg-gray-50">
                            <td colspan="4" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">Additional Information</td>
                          </tr>
                          <template x-for="(value, key) in record?.table_data?.Additional || {}" :key="key">
                            <tr>
                              <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700" x-text="key"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.original || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.harmonised || 'N/A'"></td>
                              <td class="border border-gray-300 px-3 py-2" x-text="value?.final || 'N/A'"></td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Detailed View (Current Content) -->
                  <div x-show="viewMode === 'detailed'">
                  <!-- Substance Information -->
                  <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Substance Information</div>
                    <div class="flex justify-between py-1 text-sm bg-slate-100">
                      <div class="px-1 font-semibold">Substance</div>
                      <div class="px-1" x-text="record?.substance?.name || 'N/A'"></div>
                    </div>
                    <div class="flex justify-between py-1 text-sm bg-slate-200">
                      <div class="px-1 font-semibold">CAS Number</div>
                      <div class="px-1" x-text="record?.substance?.cas_number || 'N/A'"></div>
                    </div>
                    <div class="flex justify-between py-1 text-sm bg-slate-100">
                      <div class="px-1 font-semibold">Code</div>
                      <div class="px-1">
                        <template x-if="record?.substance?.prefixed_code">
                          <a :href="'{{ route('substances.show', ':id') }}'.replace(':id', record?.substance?.id)" 
                             target="_blank" class="link-lime-text" x-text="record?.substance?.prefixed_code"></a>
                        </template>
                        <template x-if="!record?.substance?.prefixed_code">
                          <span>N/A</span>
                        </template>
                      </div>
                    </div>
                  </div>

                  <!-- Source Information -->
                  <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Source Information</div>
                    <template x-for="(value, key, index) in record?.table_data?.Source || {}" :key="key">
                      <div class="flex justify-between py-1 text-sm" :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'">
                        <div class="px-1 font-semibold" x-text="key"></div>
                        <div class="px-1">
                          <template x-if="typeof value === 'object'">
                            <div class="text-xs">
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Original:</span> 
                                <span x-text="value.original || 'N/A'"></span>
                              </div>
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Harmonised:</span> 
                                <span x-text="value.harmonised || 'N/A'"></span>
                              </div>
                              <div>
                                <span class="font-medium text-purple-600">Final:</span> 
                                <span x-text="value.final || 'N/A'"></span>
                              </div>
                            </div>
                          </template>
                          <template x-if="typeof value !== 'object'">
                            <span x-text="value || 'N/A'"></span>
                          </template>
                        </div>
                      </div>
                    </template>
                  </div>

                  <!-- Reference Information -->
                  <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Reference Information</div>
                    <template x-for="(value, key, index) in record?.table_data?.Reference || {}" :key="key">
                      <div class="flex justify-between py-1 text-sm" :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'">
                        <div class="px-1 font-semibold" x-text="key"></div>
                        <div class="px-1">
                          <template x-if="typeof value === 'object'">
                            <div class="text-xs">
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Original:</span> 
                                <span x-text="value.original || 'N/A'"></span>
                              </div>
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Harmonised:</span> 
                                <span x-text="value.harmonised || 'N/A'"></span>
                              </div>
                              <div>
                                <span class="font-medium text-purple-600">Final:</span> 
                                <span x-text="value.final || 'N/A'"></span>
                              </div>
                            </div>
                          </template>
                          <template x-if="typeof value !== 'object'">
                            <span x-text="value || 'N/A'"></span>
                          </template>
                        </div>
                      </div>
                    </template>
                  </div>

                  <!-- Test Information -->
                  <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Test Information</div>
                    <template x-for="(value, key, index) in record?.table_data?.Test || {}" :key="key">
                      <div class="flex justify-between py-1 text-sm" :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'">
                        <div class="px-1 font-semibold" x-text="key"></div>
                        <div class="px-1">
                          <template x-if="typeof value === 'object'">
                            <div class="text-xs">
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Original:</span> 
                                <span x-text="value.original || 'N/A'"></span>
                              </div>
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Harmonised:</span> 
                                <span x-text="value.original || 'N/A'"></span>
                              </div>
                              <div>
                                <span class="font-medium text-purple-600">Final:</span> 
                                <span x-text="value.final || 'N/A'"></span>
                              </div>
                            </div>
                          </template>
                          <template x-if="typeof value !== 'object'">
                            <span x-text="value || 'N/A'"></span>
                          </template>
                        </div>
                      </div>
                    </template>
                  </div>

                  <!-- Organism Information -->
                  <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Organism Information</div>
                    <template x-for="(value, key, index) in record?.table_data?.Organism || {}" :key="key">
                      <div class="flex justify-between py-1 text-sm" :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'">
                        <div class="px-1 font-semibold" x-text="key"></div>
                        <div class="px-1">
                          <template x-if="typeof value === 'object'">
                            <div class="text-xs">
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Original:</span> 
                                <span x-text="value.original || 'N/A'"></span>
                              </div>
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Harmonised:</span> 
                                <span x-text="value.harmonised || 'N/A'"></span>
                              </div>
                              <div>
                                <span class="font-medium text-purple-600">Final:</span> 
                                <span x-text="value.final || 'N/A'"></span>
                              </div>
                            </div>
                          </template>
                          <template x-if="typeof value !== 'object'">
                            <span x-text="value || 'N/A'"></span>
                          </template>
                        </div>
                      </div>
                    </template>
                  </div>

                  <!-- Concentration Information -->
                  <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Concentration Information</div>
                    <template x-for="(value, key, index) in record?.table_data?.Concentration || {}" :key="key">
                      <div class="flex justify-between py-1 text-sm" :class="index % 2 === 0 ? 'bg-slate-200' : 'bg-slate-100'">
                        <div class="px-1 font-semibold" x-text="key"></div>
                        <div class="px-1">
                          <template x-if="typeof value === 'object'">
                            <div class="text-xs">
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Original:</span> 
                                <span x-text="value.original || 'N/A'"></span>
                              </div>
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Harmonised:</span> 
                                <span x-text="value.harmonised || 'N/A'"></span>
                              </div>
                              <div>
                                <span class="font-medium text-purple-600">Final:</span> 
                                <span x-text="value.final || 'N/A'"></span>
                              </div>
                            </div>
                          </template>
                          <template x-if="typeof value !== 'object'">
                            <span x-text="value || 'N/A'"></span>
                          </template>
                        </div>
                      </div>
                    </template>
                  </div>

                  <!-- Additional Information -->
                  <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Additional Information</div>
                    <template x-for="(value, key, index) in record?.table_data?.Additional || {}" :key="key">
                      <div class="flex justify-between py-1 text-sm" :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'">
                        <div class="px-1 font-semibold" x-text="key"></div>
                        <div class="px-1">
                          <template x-if="typeof value === 'object'">
                            <div class="text-xs">
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Original:</span> 
                                <span x-text="value.original || 'N/A'"></span>
                              </div>
                              <div class="mb-1">
                                <span class="font-medium text-green-600">Harmonised:</span> 
                                <span x-text="value.harmonised || 'N/A'"></span>
                              </div>
                              <div>
                                <span class="font-medium text-purple-600">Final:</span> 
                                <span x-text="value.final || 'N/A'"></span>
                              </div>
                            </div>
                          </template>
                          <template x-if="typeof value !== 'object'">
                            <span x-text="value || 'N/A'"></span>
                          </template>
                        </div>
                      </div>
                    </template>
                  </div>
                  </div>
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="flex justify-end border-t px-4 py-2">
                <button @click="closeModal()" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                  Close
                </button>
              </div>
            </div>
          </div>

          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
  
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const tabButtons = document.querySelectorAll('.tab-button');
      const tableRows = document.querySelectorAll('.ecotox-row');
      const noResultsMessage = document.getElementById('no-results-message');
      const tableBody = document.getElementById('ecotox-table-body');
      
      // Function to filter table rows based on selected tab
      function filterTable(matrixFilter, acuteFilter) {
        let visibleCount = 0;
        
        tableRows.forEach(row => {
          const rowMatrix = row.dataset.matrix;
          const rowAcute = row.dataset.acute;
          
          // Show row if it matches the filters (or if "All" is selected)
          if (matrixFilter === '' && acuteFilter === '') {
            // Show all rows
            row.style.display = '';
            visibleCount++;
          } else if (rowMatrix === matrixFilter && rowAcute === acuteFilter) {
            row.style.display = '';
            visibleCount++;
          } else {
            row.style.display = 'none';
          }
        });
        
        // Show/hide no results message
        if (visibleCount === 0) {
          noResultsMessage.classList.remove('hidden');
          tableBody.parentElement.style.display = 'none';
        } else {
          noResultsMessage.classList.add('hidden');
          tableBody.parentElement.style.display = '';
        }
      }
      
      // Function to activate tab
      function activateTab(button) {
        const tabId = button.dataset.tab;
        const matrixFilter = button.dataset.filterMatrix;
        const acuteFilter = button.dataset.filterAcute;
        
        // Update button styles
        tabButtons.forEach(btn => {
          btn.classList.remove('border-sky-500', 'text-sky-600');
          btn.classList.add('border-transparent', 'text-gray-500');
        });
        
        button.classList.remove('border-transparent', 'text-gray-500');
        button.classList.add('border-sky-500', 'text-sky-600');
        
        // Filter table rows
        filterTable(matrixFilter, acuteFilter);
        
        // Save active tab to localStorage
        localStorage.setItem('activeEcotoxTab', tabId);
      }
      
      // Add click event to tab buttons
      tabButtons.forEach(button => {
        button.addEventListener('click', function() {
          activateTab(this);
        });
      });
      
      // Check for saved tab in localStorage and activate it
      const savedTab = localStorage.getItem('activeEcotoxTab');
      if (savedTab) {
        const savedButton = document.querySelector(`[data-tab="${savedTab}"]`);
        if (savedButton) {
          activateTab(savedButton);
        }
      }
    });
  </script>
  @endpush
  

</x-app-layout>