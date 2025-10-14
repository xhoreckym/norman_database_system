<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>


  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <form method="GET" action="{{ route('literature.search.filter') }}" class="inline">
            @if(is_array($countrySearch))
              @foreach($countrySearch as $country)
                <input type="hidden" name="countrySearch[]" value="{{ $country }}">
              @endforeach
            @else
              <input type="hidden" name="countrySearch" value="{{ $countrySearch ?? '' }}">
            @endif

            <input type="hidden" name="displayOption" value="{{ $displayOption }}">
            <input type="hidden" name="query_log_id" value="{{ $query_log_id }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </form>

          <div class="text-gray-600 flex border-l-2 border-white">
            @if ($displayOption == 1)
              {{-- use simple output --}}
              <div class="py-2">
                Number of matched records:
              </div>
              <div class="py-2 mx-1 font-bold">
                {{ number_format($literatureMatchedCount, 0, '.', ' ') }}
              </div>
              <div class="py-2">
                of <span> {{ number_format($literatureObjectsCount, 0, ' ', ' ') }}
                  @if (is_numeric($literatureMatchedCount) && $literatureObjectsCount > 0)
                    @if (($literatureMatchedCount / $literatureObjectsCount) * 100 < 0.01)
                      which is &le; 0.01% of total records.
                    @else
                      which is {{ number_format(($literatureMatchedCount / $literatureObjectsCount) * 100, 3, '.', ' ') }}% of total
                      records.
                    @endif
                  @endif
                </span>
              </div>
            @else
              {{-- use advanced output --}}
              <div class="py-2">
                Number of matched records:
              </div>
              <div class="py-2 mx-1 font-bold">
                {{ number_format($literatureRecords->total(), 0, '.', ' ') }}
              </div>

              <div class="py-2">
                of <span> {{ number_format($literatureObjectsCount, 0, ' ', ' ') }}
                  @if (is_numeric($literatureRecords->total()) && $literatureObjectsCount > 0)
                    @if (($literatureRecords->total() / $literatureObjectsCount) * 100 < 0.01)
                      which is &le; 0.01% of total records.
                    @else
                      which is {{ number_format(($literatureRecords->total() / $literatureObjectsCount) * 100, 3, '.', ' ') }}% of total
                      records.
                    @endif
                  @endif
                </span>
              </div>
            @endif
          </div>

          @if(isset($searchParameters) && !empty($searchParameters))
            <div class="text-gray-600 flex border-l-2 border-white">
              Search parameters:&nbsp;<span class="font-semibold">
                @foreach ($searchParameters as $key => $value)
                  @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                    @foreach ($value as $item)
                      {{ $item }}@if (!$loop->last), @endif
                    @endforeach
                  @else
                    {{ $value }}
                  @endif
                  @if (!$loop->last); @endif
                @endforeach
              </span>
            </div>
          @endif

          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Norman SusDat ID</th>
                <th>First Author</th>
                <th>Year</th>
                <th>Country</th>
                <th>Species (Latin Name)</th>
                <th>Life Stage</th>
                <th>Chemical Name</th>
                <th>Tissue</th>
                <th>Concentration (ng/g ww)</th>
                <th>DOI</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($literatureRecords as $record)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="font-mono text-teal-800">
                      {!! number_format($record->id, 0, '', '&nbsp;') !!}
                    </div>
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->substance && $record->substance->code)
                      <div class="font-mono text-teal-800">
                        NS{{ $record->substance->code }}
                      </div>
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    {{ $record->first_author ?? 'N/A' }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $record->year ?? 'N/A' }}
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->country)
                      {{ $record->country->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->species)
                      <span title="{{ $record->species->name_latin ?? 'N/A' }}">
                        {{ $record->species->name_latin ?? 'N/A' }}
                      </span>
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->lifeStage)
                      {{ $record->lifeStage->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    <div class="max-w-xs truncate" title="{{ $record->chemical_name }}">
                      {{ $record->chemical_name ?? 'N/A' }}
                    </div>
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->tissue)
                      {{ $record->tissue->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->ww_conc_ng)
                      {{ number_format($record->ww_conc_ng, 4) }}
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->doi)
                      <a href="https://doi.org/{{ $record->doi }}" target="_blank" class="link-lime-text text-xs" title="Open DOI link">
                        {{ $record->doi }}
                      </a>
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="p-4 text-center text-gray-500">
                    No records found. Please adjust your search criteria.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>

          @if ($displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($literatureRecords->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $literatureRecords->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($literatureRecords->hasMorePages())
                <a href="{{ $literatureRecords->nextPageUrl() }}"
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
            {{ $literatureRecords->links('pagination::tailwind') }}
          @endif

        </div>
      </div>
    </div>
  </div>

</x-app-layout>

