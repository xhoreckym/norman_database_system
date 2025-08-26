<x-app-layout>
  <x-slot name="header">
    @include('factsheet.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          {{-- main div --}}

        @if(isset($substanceData) && $substanceData->count() > 0)
          <!-- Primary information -->
          <div class="grid grid-cols-3 gap-4">
            <div class="col-span-1">
              <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                  Substance Factsheets
                </h1>
                <p class="text-gray-700">View detailed information for selected substances</p>
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
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Substance Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  @foreach($substanceData as $substance)
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
                      <h3 class="text-sm font-medium text-gray-800 mb-1">Code</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->prefixed_code ?? 'N/A' }}</p>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
          <!-- End of Primary information -->

          <!-- Placeholder for future factsheet content -->
          <div class="bg-slate-50 border border-slate-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Factsheet Information</h3>
            <p class="text-gray-600">Factsheet content will be displayed here in future updates.</p>
          </div>
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
