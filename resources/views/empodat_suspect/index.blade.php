<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <form method="GET" action="{{ route('empodat_suspect.search.filter') }}" class="inline">
            @if(is_array($stationSearch))
              @foreach($stationSearch as $station)
                <input type="hidden" name="stationSearch[]" value="{{ $station }}">
              @endforeach
            @else
              <input type="hidden" name="stationSearch" value="{{ $stationSearch ?? '' }}">
            @endif

            @if(is_array($substances))
              @foreach($substances as $substance)
                <input type="hidden" name="substances[]" value="{{ $substance }}">
              @endforeach
            @else
              <input type="hidden" name="substances" value="{{ $substances ?? '' }}">
            @endif

            <input type="hidden" name="displayOption" value="{{ $displayOption }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </form>

          <div class="text-gray-600 flex border-l-2 border-white">
            <div class="py-2">
              Number of matched records:
            </div>
            <div class="py-2 mx-1 font-bold">
              {{ number_format($matchedCount, 0, '.', ' ') }}
            </div>
            <div class="py-2">
              of <span>{{ number_format($totalCount, 0, ' ', ' ') }}
              @if (is_numeric($matchedCount) && $totalCount > 0)
                @if (($matchedCount / $totalCount) * 100 < 0.01)
                  which is &le; 0.01% of total records.
                @else
                  which is {{ number_format(($matchedCount / $totalCount) * 100, 3, '.', ' ') }}% of total records.
                @endif
              @endif
              </span>
            </div>
          </div>

          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>Num</th>
                <th>NORMAN ID</th>
                <th>Name</th>
                <th>IP</th>
                <th>IP_max</th>
                <th>Based on HRMS Library</th>
                <th>Units</th>
                @foreach($stationMappings as $mapping)
                  <th>{{ $mapping->xlsx_name }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @forelse($pivotedData as $row)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 text-center">{{ $row['num'] }}</td>
                  <td class="p-1 text-center">
                    <div class="font-mono text-teal-800">
                      {{ $row['norman_id'] }}
                    </div>
                  </td>
                  <td class="p-1 text-center">{{ $row['name'] }}</td>
                  <td class="p-1 text-center text-xs">
                    @php
                      $ip = $row['ip'];
                      $displayIp = $ip;

                      // If IP is long, truncate it intelligently
                      if (strlen($ip) > 50) {
                        $parts = array_map('trim', explode(',', $ip));

                        if (count($parts) > 5) {
                          // Show first 3, ..., and last 1
                          $displayIp = implode(', ', array_slice($parts, 0, 3)) . ', ..., ' . end($parts);
                        } else {
                          // Just truncate at 50 chars
                          $displayIp = substr($ip, 0, 47) . '...';
                        }
                      }
                    @endphp
                    {{ $displayIp }}
                  </td>
                  <td class="p-1 text-center">{{ $row['ip_max'] }}</td>
                  <td class="p-1 text-center">{{ $row['based_on_hrms_library'] }}</td>
                  <td class="p-1 text-center">{{ $row['units'] }}</td>
                  @foreach($stationMappings as $mapping)
                    <td class="p-1 text-center">
                      {{ $row['stations'][$mapping->xlsx_name] }}
                    </td>
                  @endforeach
                </tr>
              @empty
                <tr>
                  <td colspan="{{ 7 + count($stationMappings) }}" class="p-4 text-center text-gray-500">
                    No results found. Please adjust your search criteria.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>

          <div class="mt-4">
            <a href="{{ route('empodat_suspect.search.filter') }}" class="btn-clear">
              ← Back to Search
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>

</x-app-layout>
