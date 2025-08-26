<x-app-layout>
  <x-slot name="header">
    @include('factsheet.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          {{-- main div --}}

          @if (isset($substance) && $substance)
            <!-- Primary information -->
            <div class="grid grid-cols-3 gap-4">
              <div class="col-span-1">
                <div class="mb-6">
                  <h1 class="text-2xl font-bold text-teal-800 font-mono mb-2">
                    {{ $substance->name ?? 'Substance Factsheet' }} 
                  </h1>
                </div>
                <div class="mb-4">
                  <a href="{{ route('factsheets.search.filter') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                    ← New Search
                  </a>
                </div>
              </div>
              <div class="col-span-2">
                <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                  <h2 class="text-lg font-semibold text-gray-900 mb-4">Substance Information at Glance</h2>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                      <h3 class="text-sm font-medium text-gray-800 mb-1">Substance</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                      <h3 class="text-sm font-medium text-gray-800 mb-1">CAS Number</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->cas_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                      <h3 class="text-sm font-medium text-gray-800 mb-1">StdInChIKey</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->stdinchikey ?? 'N/A' }}</p>
                    </div>
                    <div>
                      <h3 class="text-sm font-medium text-gray-800 mb-1">NORMAN Code</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->prefixed_code ?? 'N/A' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End of Primary information -->

                      <!-- Factsheet Sections -->
          @if(isset($factsheetEntities) && $factsheetEntities->count() > 0)
            <div class="space-y-6">
              @foreach($factsheetEntities as $entity)
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm" x-data="{ open: false }">
                  <!-- Section Header (Always Visible) -->
                  <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                      <div class="flex items-center space-x-3">
                        <button 
                          @click="open = !open" 
                          class="flex items-center space-x-2 text-left hover:text-slate-600 transition-colors duration-200"
                          :aria-expanded="open"
                        >
                          <!-- Collapsible Arrow -->
                          <svg 
                            class="w-5 h-5 text-slate-500 transition-transform duration-200" 
                            :class="{ 'rotate-90': open }"
                            xmlns="http://www.w3.org/2000/svg" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                          </svg>
                          <h3 class="text-lg font-semibold text-gray-900">{{ $entity->name }}</h3>
                        </button>
                      </div>
                      <span class="text-sm text-gray-500">Section {{ $entity->sort_order }}</span>
                    </div>
                  </div>
                  
                  <!-- Collapsible Content -->
                  <div 
                    x-show="open" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform translate-y-0"
                    x-transition:leave-end="opacity-0 transform -translate-y-2"
                    class="p-6"
                  >
                    @if(isset($entity->data['method_of_presentation']))
                      @if($entity->data['method_of_presentation'] === 'database_table')
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                          <p class="text-sm text-slate-600 mb-2">Database table presentation</p>
                          <p class="text-xs text-slate-500">Model: {{ $entity->data['model'] ?? 'N/A' }}</p>
                          @if(isset($entity->data['fields']))
                            <p class="text-xs text-slate-500">Fields: {{ implode(', ', $entity->data['fields']) }}</p>
                          @endif
                        </div>
                      @elseif($entity->data['method_of_presentation'] === 'text')
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                          <p class="text-sm text-slate-600">{{ $entity->data['text'] ?? 'No text content available' }}</p>
                        </div>
                      @endif
                    @else
                      <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                        <p class="text-sm text-slate-600">Table content will be specified later</p>
                      </div>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-6 text-center">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">No Factsheet Sections Available</h3>
              <p class="text-gray-600">Factsheet sections have not been configured yet.</p>
            </div>
          @endif
          @else
            <div class="mb-6 bg-slate-50 border border-slate-200 rounded-lg p-6 text-center">
              <h2 class="text-lg font-semibold text-gray-900 mb-2">No Substances Selected</h2>
              <p class="text-gray-600 mb-4">Please search for substances to view their factsheet information.</p>
              <a href="{{ route('factsheets.search.filter') }}" class="btn-submit">
                Start Substance Search
              </a>
            </div>
          @endif

          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
