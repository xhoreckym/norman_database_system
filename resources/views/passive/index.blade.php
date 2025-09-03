<x-app-layout>
  <x-slot name="header">
    @include('passive.header')
  </x-slot>


  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          {{-- main div --}}

          <div class="flex items-center space-x-4 mb-4">
            <a
              href="{{ route('passive.search.filter', [
                  'countrySearch' => $request->input('countrySearch'),
                  'matrixSearch' => $request->input('matrixSearch'),
                  'year_to' => $request->input('year_to'),
                  'year_from' => $request->input('year_from'),
                  'substances' => $request->input('substances'),
                  'query_log_id' => $query_log_id,
              ]) }}">
              <button type="submit" class="btn-submit">Refine Search</button>
            </a>

            @if (isset($request->displayOption) && $request->displayOption == 1)
              {{-- Simple output --}}
              @livewire('backend.query-counter', [
                  'queryId' => $query_log_id ?? null,
                  'resultsCount' => $resultsObjectsCount,
                  'count_again' => request()->has('page') ? false : true,
              ])
            @else
              {{-- Advanced output with better styling --}}
              <div class="flex items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-center mr-4">
                  <span class="text-gray-700">Number of matched records:</span>
                  <span class="font-bold text-lg ml-2 text-sky-700">
                    {{ number_format($resultsObjects->total(), 0, '.', ' ') }}
                  </span>
                </div>

                <div class="flex items-center">
                  <span class="text-gray-700">of</span>
                  <span class="font-medium ml-2 text-gray-800">
                    {{ number_format($resultsObjectsCount, 0, '.', ' ') }}
                  </span>

                  @if (is_numeric($resultsObjects->total()) && $resultsObjectsCount > 0)
                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                      @if (($resultsObjects->total() / $resultsObjectsCount) * 100 < 0.01)
                        &le; 0.01% of total
                      @else
                        {{ number_format(($resultsObjects->total() / $resultsObjectsCount) * 100, 2, '.', ' ') }}% of
                        total
                      @endif
                    </span>
                  @endif
                </div>
              </div>
            @endif

            @auth
              <a href="{{ route('passive.search.download', ['query_log_id' => $query_log_id]) }}"
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
                <th>Substance</th>
                <th>Country</th>
                <th>Matrix</th>
                <th>Organisation</th>
                <th>Date of sampling</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($resultsObjects as $e)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="flex justify-center space-x-1">
                      <a href="{{ route('passive.search.show', ['search' => $e->id] + $request->all()) }}" class="link-lime" title="View Details">
                        <i class="fas fa-search"></i>
                      </a>
                      @if (auth()->check() &&
                              (auth()->user()->hasRole('super_admin') ||
                                  auth()->user()->hasRole('admin') ||
                                  auth()->user()->hasRole('passive')))
                        <a href="{{ route('passive.search.edit', ['search' => $e->id] + $request->all()) }}" class="link-edit" title="Edit Record">
                          <i class="fas fa-edit"></i>
                        </a>
                      @endif
                    </div>
                  </td>
                  <td class="p-1 text-center">{{ $e->id }}</td>
                  <td class="p-1 text-center">
                    @if ($e->sus_id)
                      {{ $e->substance->name ?? 'N/A' }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->country_id)
                      {{ $e->country->name ?? $e->country_id }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->matrix_id)
                      {{ $e->matrix->name }}
                    @elseif($e->matrix_other)
                      {{ $e->matrix_other }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->org_id)
                      {{ $e->organisation->name ?? 'Org ID: ' . $e->org_id }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->date_sampling_start_year)
                      {{ $e->date_sampling_start_year }}-{{ sprintf('%02d', $e->date_sampling_start_month) }}-{{ sprintf('%02d', $e->date_sampling_start_day) }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          @if (isset($request->displayOption) && $request->displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($resultsObjects->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $resultsObjects->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($resultsObjects->hasMorePages())
                <a href="{{ $resultsObjects->nextPageUrl() }}"
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
            {{ $resultsObjects->links('pagination::tailwind') }}
          @endif



          <!-- The Modal (hidden by default) -->




          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
