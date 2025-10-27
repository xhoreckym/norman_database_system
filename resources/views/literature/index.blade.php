<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <form method="GET" action="{{ route('literature.search.filter') }}" class="inline">
            {{-- Country Search --}}
            @foreach(($countrySearch ?? []) as $country)
              <input type="hidden" name="countrySearch[]" value="{{ $country }}">
            @endforeach

            {{-- Species Search --}}
            @foreach(($speciesSearch ?? []) as $species)
              <input type="hidden" name="speciesSearch[]" value="{{ $species }}">
            @endforeach

            {{-- Class Search --}}
            @foreach(($classSearch ?? []) as $class)
              <input type="hidden" name="classSearch[]" value="{{ $class }}">
            @endforeach

            {{-- Tissue Search --}}
            @foreach(($tissueSearch ?? []) as $tissue)
              <input type="hidden" name="tissueSearch[]" value="{{ $tissue }}">
            @endforeach

            {{-- Matrix Search --}}
            @foreach(($matrixSearch ?? []) as $matrix)
              <input type="hidden" name="matrixSearch[]" value="{{ $matrix }}">
            @endforeach

            {{-- Type of Numeric Quantity Search --}}
            @foreach(($typeOfNumericQuantitySearch ?? []) as $quantity)
              <input type="hidden" name="typeOfNumericQuantitySearch[]" value="{{ $quantity }}">
            @endforeach

            {{-- Categories Search --}}
            @foreach(($categoriesSearch ?? []) as $category)
              <input type="hidden" name="categoriesSearch[]" value="{{ $category }}">
            @endforeach

            {{-- File Search --}}
            @foreach(($fileSearch ?? []) as $file)
              <input type="hidden" name="fileSearch[]" value="{{ $file }}">
            @endforeach

            {{-- Project Search --}}
            @foreach(($projectSearch ?? []) as $project)
              <input type="hidden" name="projectSearch[]" value="{{ $project }}">
            @endforeach

            {{-- Substances --}}
            @foreach(($substances ?? []) as $substance)
              <input type="hidden" name="substances[]" value="{{ $substance }}">
            @endforeach

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
                <th>Norman SUS ID</th>
                <th>Chemical Name</th>
                <th>Concentration<br>(ng/g ww)</th>
                <th>LOD<br>(ng/g ww)</th>
                <th>LOQ<br>(ng/g ww)</th>
                <th>Standard Deviation<br>(ng/g ww)</th>
                <th>Species (Class)</th>
                <th>Sampling Start</th>
                <th>Sampling End</th>
                <th>Country</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($literatureRecords as $record)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <a href="{{ route('literature.search.show', $record->id) }}" class="font-mono text-teal-800 hover:text-teal-600 hover:underline">
                      {!! number_format($record->id, 0, '', '&nbsp;') !!}
                    </a>
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->substance && $record->substance->code)
                      <a href="{{ route('substances.show', $record->substance->id) }}" class="font-mono text-teal-800 hover:text-teal-600 hover:underline">
                        NS{{ $record->substance->code }}
                      </a>
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->substance && $record->substance->name)
                      <div class="max-w-xs truncate" title="{{ $record->substance->name }}">
                        {{ $record->substance->name }}
                      </div>
                    @elseif ($record->chemical_name)
                      <div class="max-w-xs truncate text-gray-600 italic" title="Original name: {{ $record->chemical_name }}">
                        original name: {{ $record->chemical_name }}
                      </div>
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->ww_conc_ng !== null)
                      {{ number_format($record->ww_conc_ng, 4) }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->ww_lod_ng !== null)
                      {{ number_format($record->ww_lod_ng, 4) }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->ww_loq_ng !== null)
                      {{ number_format($record->ww_loq_ng, 4) }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->ww_sd_ng !== null)
                      {{ number_format($record->ww_sd_ng, 4) }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->species)
                      @php
                        $speciesDisplay = $record->species->name ?? '';
                        if ($record->species->name_latin) {
                          $speciesDisplay .= ' (' . $record->species->name_latin . ')';
                        }
                        if ($record->species->class) {
                          $speciesDisplay .= ' [' . $record->species->class . ']';
                        }
                      @endphp
                      <div class="max-w-xs truncate" title="{{ $speciesDisplay }}">
                        {{ $speciesDisplay }}
                      </div>
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @php
                      $startDate = [];
                      if ($record->start_of_sampling_year) $startDate[] = $record->start_of_sampling_year;
                      if ($record->start_of_sampling_month) $startDate[] = $record->start_of_sampling_month;
                      if ($record->start_of_sampling_day) $startDate[] = $record->start_of_sampling_day;
                      echo !empty($startDate) ? implode('-', $startDate) : '<span class="text-gray-400">N/A</span>';
                    @endphp
                  </td>
                  <td class="p-1 text-center">
                    @php
                      $endDate = [];
                      if ($record->end_of_sampling_year) $endDate[] = $record->end_of_sampling_year;
                      if ($record->end_of_sampling_month) $endDate[] = $record->end_of_sampling_month;
                      if ($record->end_of_sampling_day) $endDate[] = $record->end_of_sampling_day;
                      echo !empty($endDate) ? implode('-', $endDate) : '<span class="text-gray-400">N/A</span>';
                    @endphp
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->country && $record->country->name)
                      {{ $record->country->name }}
                    @else
                      <span class="text-gray-400">N/A</span>
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

