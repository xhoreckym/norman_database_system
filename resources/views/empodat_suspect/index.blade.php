<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <form method="GET" action="{{ route('empodat_suspect.search.filter') }}" class="inline">
            @if(is_array($countrySearch))
              @foreach($countrySearch as $country)
                <input type="hidden" name="countrySearch[]" value="{{ $country }}">
              @endforeach
            @else
              <input type="hidden" name="countrySearch" value="{{ $countrySearch ?? '' }}">
            @endif

            @if(is_array($matrixSearch))
              @foreach($matrixSearch as $matrix)
                <input type="hidden" name="matrixSearch[]" value="{{ $matrix }}">
              @endforeach
            @else
              <input type="hidden" name="matrixSearch" value="{{ $matrixSearch ?? '' }}">
            @endif

            @if(is_array($sourceSearch))
              @foreach($sourceSearch as $source)
                <input type="hidden" name="sourceSearch[]" value="{{ $source }}">
              @endforeach
            @else
              <input type="hidden" name="sourceSearch" value="{{ $sourceSearch ?? '' }}">
            @endif

            <input type="hidden" name="year_from" value="{{ $year_from ?? '' }}">
            <input type="hidden" name="year_to" value="{{ $year_to ?? '' }}">
            <input type="hidden" name="displayOption" value="{{ $displayOption }}">

            @if(is_array($substances))
              @foreach($substances as $substance)
                <input type="hidden" name="substances[]" value="{{ $substance }}">
              @endforeach
            @else
              <input type="hidden" name="substances" value="{{ $substances ?? '' }}">
            @endif

            @if(is_array($categoriesSearch))
              @foreach($categoriesSearch as $category)
                <input type="hidden" name="categoriesSearch[]" value="{{ $category }}">
              @endforeach
            @else
              <input type="hidden" name="categoriesSearch" value="{{ $categoriesSearch ?? '' }}">
            @endif

            @if(is_array($typeDataSourcesSearch))
              @foreach($typeDataSourcesSearch as $typeDataSource)
                <input type="hidden" name="typeDataSourcesSearch[]" value="{{ $typeDataSource }}">
              @endforeach
            @else
              <input type="hidden" name="typeDataSourcesSearch" value="{{ $typeDataSourcesSearch ?? '' }}">
            @endif

            @if(is_array($concentrationIndicatorSearch))
              @foreach($concentrationIndicatorSearch as $concentrationIndicator)
                <input type="hidden" name="concentrationIndicatorSearch[]" value="{{ $concentrationIndicator }}">
              @endforeach
            @else
              <input type="hidden" name="concentrationIndicatorSearch" value="{{ $concentrationIndicatorSearch ?? '' }}">
            @endif

            @if(is_array($analyticalMethodSearch))
              @foreach($analyticalMethodSearch as $analyticalMethod)
                <input type="hidden" name="analyticalMethodSearch[]" value="{{ $analyticalMethod }}">
              @endforeach
            @else
              <input type="hidden" name="analyticalMethodSearch" value="{{ $analyticalMethodSearch ?? '' }}">
            @endif

            @if(is_array($dataSourceLaboratorySearch))
              @foreach($dataSourceLaboratorySearch as $dataSourceLaboratory)
                <input type="hidden" name="dataSourceLaboratorySearch[]" value="{{ $dataSourceLaboratory }}">
              @endforeach
            @else
              <input type="hidden" name="dataSourceLaboratorySearch" value="{{ $dataSourceLaboratorySearch ?? '' }}">
            @endif

            @if(is_array($dataSourceOrganisationSearch))
              @foreach($dataSourceOrganisationSearch as $dataSourceOrganisation)
                <input type="hidden" name="dataSourceOrganisationSearch[]" value="{{ $dataSourceOrganisation }}">
              @endforeach
            @else
              <input type="hidden" name="dataSourceOrganisationSearch" value="{{ $dataSourceOrganisationSearch ?? '' }}">
            @endif

            @if(is_array($qualityAnalyticalMethodsSearch))
              @foreach($qualityAnalyticalMethodsSearch as $qualityAnalyticalMethod)
                <input type="hidden" name="qualityAnalyticalMethodsSearch[]" value="{{ $qualityAnalyticalMethod }}">
              @endforeach
            @else
              <input type="hidden" name="qualityAnalyticalMethodsSearch" value="{{ $qualityAnalyticalMethodsSearch ?? '' }}">
            @endif

            <input type="hidden" name="query_log_id" value="{{ $query_log_id }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </form>

          <div class="text-gray-600 flex border-l-2 border-white">
            @if ($displayOption == 1)
              {{-- Simple output - use Livewire query counter --}}
              @livewire('backend.query-counter', [
                  'queryId' => $query_log_id ?? null,
                  'resultsCount' => $empodatSuspectsCount,
                  'count_again' => request()->has('page') ? false : true,
              ])
            @else
              {{-- Advanced output with pagination --}}
              <div class="py-2">
                Number of matched records:
              </div>
              <div class="py-2 mx-1 font-bold">
                {{ number_format($empodatSuspects->total(), 0, '.', ' ') }}
              </div>

              @if ($empodatSuspectsCount > 0)
                <div class="py-2">
                  of <span> {{ number_format($empodatSuspectsCount, 0, ' ', ' ') }}
                    @if (is_numeric($empodatSuspects->total()))
                      @if (($empodatSuspects->total() / $empodatSuspectsCount) * 100 < 0.01)
                        which is &le; 0.01% of total records.
                      @else
                        which is {{ number_format(($empodatSuspects->total() / $empodatSuspectsCount) * 100, 3, '.', ' ') }}% of total records.
                      @endif
                    @endif
                  </span>
                </div>
              @endif
            @endif
          </div>

          @if (!empty($searchParameters))
          <div class="text-gray-600 flex border-l-2 border-white">
            Search parameters:&nbsp;<span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
                {{-- if value is array|collection then use for each, otherwise display value --}}
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
          @endif

          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Substance</th>
                <th>Concentration</th>
                <th>Units</th>
                <th>IP_max</th>
                <th>HRMS Library</th>
                <th>Country</th>
                <th>Year</th>
                <th>Sample code</th>
                <th>Sampling station</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($empodatSuspects as $e)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="flex items-center justify-center space-x-2">
                      <a href="{{ route('empodat_suspect.search.show', $e->id) }}"
                         target="_blank"
                         class="text-teal-600 hover:text-teal-800 transition-colors"
                         title="View full record details">
                        <i class="fas fa-search text-sm"></i>
                      </a>
                      <a href="{{ route('empodat_suspect.search.show', $e->id) }}" target="_blank" class="font-mono text-teal-800 hover:text-teal-600 hover:underline">
                        {!! number_format($e->id, 0, '', '&nbsp;') !!}
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
                    <span class="font-medium">{{ $e->concentration ?? 'N/A' }}</span>
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->units ?? 'N/A' }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->ip_max ?? 'N/A' }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->based_on_hrms_library ? 'TRUE' : 'FALSE' }}
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
                    {{ $e->sampling_year ?? 'N/A' }}
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station)
                      <span class="font-mono text-sm">{{ $e->station->short_sample_code ?? 'N/A' }}</span>
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station)
                      {{ $e->station->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="p-4 text-center text-gray-500">
                    No results found. Please adjust your search criteria.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>

          @if ($displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($empodatSuspects->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $empodatSuspects->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($empodatSuspects->hasMorePages())
                <a href="{{ $empodatSuspects->nextPageUrl() }}"
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
            {{ $empodatSuspects->links('pagination::tailwind') }}
          @endif

        </div>
      </div>
    </div>
  </div>

</x-app-layout>
