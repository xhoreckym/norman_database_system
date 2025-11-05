<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Notification Banner --}}
          <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500 text-lg"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-blue-700">
                  <strong>Note:</strong> This record is displayed in a new browser tab.
                  To return to your search results, please switch to the previous tab instead of using the "Search" link in the navigation bar.
                </p>
              </div>
            </div>
          </div>

          {{-- Empodat Suspect Record Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Empodat Suspect Record Information at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->id ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Norman SUS ID</h3>
                <p class="text-sm text-teal-800 font-mono">
                  @if ($record->substance && $record->substance->code)
                    <a href="{{ route('substances.show', $record->substance->id) }}" class="link-lime-text">
                      NS{{ $record->substance->code }}
                    </a>
                  @else
                    N/A
                  @endif
                </p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Substance Name</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->substance->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Concentration</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->concentration ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Units</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->units ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">IP Max</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->ip_max ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Based on HRMS Library</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->based_on_hrms_library ? 'TRUE' : 'FALSE' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Country</h3>
                <p class="text-sm text-teal-800 font-mono">
                  @if ($record->station && $record->station->country)
                    {{ $record->station->country->name ?? 'N/A' }} - {{ $record->station->country->code ?? 'N/A' }}
                  @else
                    N/A
                  @endif
                </p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Sampling Station</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->station->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Sample Code</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->station->short_sample_code ?? 'N/A' }}</p>
              </div>
            </div>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full overflow-x-auto">
            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4" style="table-layout: fixed;">
              @foreach ($record->toArray() as $key => $value)
                {{-- Skip substance and station - already shown in "at Glance" section --}}
                @if ($key === 'substance' || $key === 'station' || $key === 'xlsx_station_mapping' || $key === 'files')
                  @continue
                @endif

                {{-- Skip all ID fields except the main record ID - we'll show the relationship names instead --}}
                @if (str_ends_with($key, '_id') && $key !== 'id')
                  @continue
                @endif

                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">{{ $key }}</td>
                  <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                    @if (is_array($value))
                      @if (!empty($value))
                        {{-- Check if this is a relationship array (has 'name' key) --}}
                        @if (isset($value['name']))
                          {{ $value['name'] }}
                        @else
                          {{-- For other arrays, show each item --}}
                          @foreach ($value as $item)
                            <div class="py-1">{{ is_array($item) ? json_encode($item) : $item }}</div>
                          @endforeach
                        @endif
                      @else
                        <span class="text-gray-500">No data available</span>
                      @endif
                    @else
                      {{ $value ?? '' }}
                    @endif
                  </td>
                </tr>
              @endforeach

              {{-- Add station information --}}
              @if ($record->station)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Station Information</td>
                </tr>
                @foreach ($record->station->toArray() as $key => $value)
                  @if ($key === 'country')
                    @continue
                  @endif
                  <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">station.{{ $key }}</td>
                    <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                      @if (is_array($value))
                        @if (!empty($value))
                          @if (isset($value['name']))
                            {{ $value['name'] }}
                          @else
                            @foreach ($value as $item)
                              <div class="py-1">{{ is_array($item) ? json_encode($item) : $item }}</div>
                            @endforeach
                          @endif
                        @else
                          <span class="text-gray-500">No data available</span>
                        @endif
                      @else
                        {{ $value ?? '' }}
                      @endif
                    </td>
                  </tr>
                @endforeach

                {{-- Add country information if available --}}
                @if ($record->station->country)
                  <tr class="bg-slate-100">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">station.country.name</td>
                    <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">{{ $record->station->country->name ?? 'N/A' }}</td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">station.country.code</td>
                    <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">{{ $record->station->country->code ?? 'N/A' }}</td>
                  </tr>
                @endif
              @endif

              {{-- Add substance information --}}
              @if ($record->substance)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Substance Information</td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">substance.id</td>
                  <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">{{ $record->substance->id ?? 'N/A' }}</td>
                </tr>
                <tr class="bg-slate-200">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">substance.code</td>
                  <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                    @if ($record->substance->code)
                      <a href="{{ route('substances.show', $record->substance->id) }}" class="link-lime-text">
                        NS{{ $record->substance->code }}
                      </a>
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
                <tr class="bg-slate-100">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">substance.name</td>
                  <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">{{ $record->substance->name ?? 'N/A' }}</td>
                </tr>
              @endif
            </table>

          </div>
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
