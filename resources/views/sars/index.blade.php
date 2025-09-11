<x-app-layout>
  <x-slot name="header">
    @include('sars.header')
  </x-slot>


  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="" x-init="initLeaflet()">
          {{-- main div --}}

          <div class="flex items-center space-x-4 mb-4">
            <a
              href="{{ route('sars.search.filter', [
                  'countrySearch' => $request->input('countrySearch'),
                  'matrixSearch' => $request->input('matrixSearch'),
                  'year_from' => $request->input('year_from'),
                  'year_to' => $request->input('year_to'),
                  'displayOption' => $request->input('displayOption'),
                  'query_log_id' => $query_log_id,
              ]) }}">
              <button type="submit" class="btn-submit">Refine Search</button>
            </a>

            @if (isset($request->displayOption) && $request->displayOption == 1)
              {{-- Simple output --}}
              @livewire('backend.query-counter', [
                  'queryId' => $query_log_id ?? null,
                  'resultsCount' => $sarsObjectsCount,
                  'count_again' => request()->has('page') ? false : true,
              ])
            @else
              {{-- Advanced output with better styling --}}
              <div class="flex items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-center mr-4">
                  <span class="text-gray-700">Number of matched records:</span>
                  <span class="font-bold text-lg ml-2 text-sky-700">
                    {{ number_format($sarsObjects->total(), 0, '.', ' ') }}
                  </span>
                </div>

                <div class="flex items-center">
                  <span class="text-gray-700">of</span>
                  <span class="font-medium ml-2 text-gray-800">
                    {{ number_format($sarsObjectsCount, 0, '.', ' ') }}
                  </span>

                  @if (is_numeric($sarsObjects->total()) && $sarsObjectsCount > 0)
                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                      @if (($sarsObjects->total() / $sarsObjectsCount) * 100 < 0.01)
                        &le; 0.01% of total
                      @else
                        {{ number_format(($sarsObjects->total() / $sarsObjectsCount) * 100, 2, '.', ' ') }}% of total
                      @endif
                    </span>
                  @endif
                </div>
              </div>
            @endif

            @auth
              <a href="{{ route('sars.search.download', ['query_log_id' => $query_log_id]) }}"
                class="btn-download">Download</a>
            @else
              <div class="text-gray-400">Downloads are available for registered users only</div>
            @endauth
          </div>

          <div class="text-gray-600 p-4 bg-gray-50 rounded-lg border border-gray-200 mb-4">
            <span class="font-medium">Search parameters:</span>&nbsp;<span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
                @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                  @foreach ($value as $item)
                    {{ $item }}@if (!$loop->last)
                      ,
                    @endif
                  @endforeach
                @else
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
                <th></th>
                <th>ID</th>
                <th>Sampling date</th>
                <th>Gene copy [number/mL of sample]</th>
                <th>Gene copy [number/ng of RNA]</th>
                <th>Ct #</th>
                <th>Sampling site</th>
                <th>Population served</th>
                <th>No. of people SARS-CoV-2 POSITIVE: activate to sort column descending</th>
                <th>Country</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($sarsObjects as $e)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="flex justify-center space-x-1">
                      <a href="{{ route('sars.search.show', $e->id) }}" class="link-lime" title="View Details">
                        <i class="fas fa-search"></i>
                      </a>
                      @if (auth()->check() &&
                              (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('sars')))
                        <a href="{{ route('sars.search.edit', $e->id) }}" class="link-edit" title="Edit Record">
                          <i class="fas fa-edit"></i>
                        </a>
                      @endif
                    </div>
                  </td>
                  <td class="p-1 text-center">
                    <div class="">
                      {{ $e->id }}
                    </div>
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->sample_from_year . '-' . $e->sample_from_month . '-' . $e->sample_from_day }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->gene1 }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->gene2 }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->ct }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->station_name }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->population_served }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->people_positive }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->name_of_country }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          @if (isset($request->displayOption) && $request->displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($sarsObjects->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $sarsObjects->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($sarsObjects->hasMorePages())
                <a href="{{ $sarsObjects->nextPageUrl() }}"
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
            {{ $sarsObjects->links('pagination::tailwind') }}
          @endif

        </div>
      </div>
    </div>
  </div>

</x-app-layout>
