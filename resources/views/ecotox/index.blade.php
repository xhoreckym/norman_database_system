<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="ecotoxTable()">
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
                {{-- if value is array|collection then use for each, othervise display value --}}
                @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                  {{-- If $value is an array or collection, loop over each element --}}
                  @foreach ($value as $item)
                    {{ $item }}@if(!$loop->last), @endif
                  @endforeach
                @else
                  {{-- Otherwise, just display the single value --}}
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
                  <span class="ml-2 py-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
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
                        <a href="#" class="link-lime-text" title="Click to view details" x-on:click.prevent="openModal('{{ $e->ecotox_id }}')">
                          {{ $e->ecotox_id ?? 'N/A' }}
                        </a>
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
          
          <!-- The Modal (hidden by default) -->
          <div class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
            x-show="showModal" x-transition>
            <div class="bg-white w-11/12 md:w-2/3 lg:w-1/2 xl:w-1/3 rounded shadow-lg relative">
              <!-- Modal Header -->
              <div class="flex justify-between items-center border-b px-4 py-2">
                <div class="flex items-center space-x-4">
                  <h3 class="text-lg font-semibold">Search Ecotox data – metadata</h3>
                </div>
                <button @click="closeModal()" class="text-gray-500 hover:text-gray-700 text-xl">
                  &times;
                </button>
              </div>

              <!-- Substance Sub-header -->
              <div class="px-4 py-2 bg-gray-50 border-b">
                <h4 class="text-md font-medium text-gray-700">Substance: <span x-text="record?.substance?.name || 'N/A'"></span></h4>
              </div>

              <!-- Search Bar -->
              <div class="px-4 py-2 border-b">
                <div class="flex justify-end">
                  <input type="text" placeholder="Search..." class="px-3 py-1 border border-gray-300 rounded text-sm">
                </div>
              </div>

              <!-- Modal Content -->
              <div class="p-4 max-h-[60vh] overflow-y-auto">
                <!-- Loading State -->
                <div x-show="!record" class="text-center py-8">
                  <div class="text-gray-500">Loading data...</div>
                </div>

                <!-- Raw Data Debug Dump -->
                <div x-show="record" class="mb-6 p-4 bg-gray-100 rounded">
                  <h4 class="font-semibold mb-2">Raw Data Debug (Controller Response):</h4>
                  <div class="text-xs bg-white p-2 rounded border overflow-auto max-h-32">
                    <pre x-text="JSON.stringify(record, null, 2)"></pre>
                  </div>
                </div>

                <!-- Error State -->
                <div x-show="record && !record.table_data" class="text-center py-8">
                  <div class="text-red-500">No data available for this record.</div>
                </div>

                <!-- Table Structure -->
                <div x-show="record && record.table_data">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-gray-100 border-b">
                        <th class="text-left p-2 font-semibold">Parameter</th>
                        <th class="text-left p-2 font-semibold">Original database entry</th>
                        <th class="text-left p-2 font-semibold">Harmonized data entry</th>
                        <th class="text-left p-2 font-semibold">Final database entry</th>
                        <th class="text-left p-2 font-semibold">Editor</th>
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Debug info -->
                      <tr x-show="record && record.table_data">
                        <td colspan="5" class="p-2 text-xs text-gray-500">
                          Debug: Found <span x-text="Object.keys(record?.table_data || {}).length"></span> sections
                        </td>
                      </tr>
                      <tr x-show="record && record.table_data">
                        <td colspan="5" class="p-2 text-xs text-gray-500">
                          Table data keys: <span x-text="Object.keys(record?.table_data || {}).join(', ')"></span>
                        </td>
                      </tr>
                      <tr x-show="record && record.table_data">
                        <td colspan="5" class="p-2 text-xs text-gray-500">
                          First section (Source) keys: <span x-text="Object.keys(record?.table_data?.Source || {}).join(', ')"></span>
                        </td>
                      </tr>
                      
                      <!-- Manual Test Row - Show if data exists -->
                      <tr x-show="record?.table_data?.Source" class="border-b bg-yellow-50">
                        <td class="p-2 font-medium text-gray-700">Manual Test - Biotest ID</td>
                        <td class="p-2" x-text="record?.table_data?.Source?.['Biotest ID']?.original || 'N/A'"></td>
                        <td class="p-2" x-text="record?.table_data?.Source?.['Biotest ID']?.harmonised || 'N/A'"></td>
                        <td class="p-2" x-text="record?.table_data?.Source?.['Biotest ID']?.final || 'N/A'"></td>
                        <td class="p-2">
                          <span class="text-gray-400">-</span>
                        </td>
                      </tr>

                      <!-- This will be populated by Alpine.js -->
                      <template x-for="(section, sectionName) in record?.table_data" :key="sectionName">
                        <!-- Section Header Row -->
                        <tr class="bg-gray-200">
                          <td colspan="5" class="p-2 font-semibold text-gray-700" x-text="sectionName"></td>
                        </tr>
                        <!-- Section Data Rows -->
                        <template x-for="(parameter, paramName) in section" :key="paramName">
                          <tr class="border-b hover:bg-gray-50">
                            <td class="p-2 font-medium text-gray-700" x-text="paramName"></td>
                            <td class="p-2" x-text="parameter.original"></td>
                            <td class="p-2" x-text="parameter.harmonised"></td>
                            <td class="p-2" x-text="parameter.final"></td>
                            <td class="p-2">
                              <!-- Placeholder for editor functionality -->
                              <span class="text-gray-400">-</span>
                            </td>
                          </tr>
                        </template>
                      </template>
                    </tbody>
                  </table>
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
    // Alpine.js function for ecotox table functionality
    function ecotoxTable() {
      return {
        showModal: false,
        record: null,
        recordId: null,

        async openModal(recordId) {
          this.recordId = recordId;
          console.log('Opening modal for record:', recordId);
          
          // Show the modal first
          this.showModal = true;
          
          try {
            // Fetch record data from the ecotox show route
            const response = await fetch(
              "{{ route('ecotox.show', ':id') }}"
              .replace(':id', recordId)
            );

            if (!response.ok) {
              console.error('Failed to fetch record data:', response.status, response.statusText);
              return;
            }

            this.record = await response.json();
            console.log('Received data:', this.record);
            console.log('Table data keys:', Object.keys(this.record.table_data || {}));
            console.log('First section data:', this.record.table_data?.Source);
            
          } catch (error) {
            console.error('Error fetching data:', error);
          }
        },

        closeModal() {
          this.showModal = false;
          this.record = null;
          this.recordId = null;
        }
      }
    }

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