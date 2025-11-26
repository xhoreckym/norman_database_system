@extends('bioassay.statistics.layout')

@section('page-title', 'Bioassay Statistics Overview')

@section('main-content')
  <!-- Database Overview Card -->
  <div class="bg-slate-600 text-white rounded-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-2xl font-bold mb-2">Bioassay Database</h3>
        <p class="text-slate-200">Effect-based monitoring data from field studies</p>
      </div>
      <div class="text-right">
        <div class="text-3xl font-bold">{{ number_format($totalRecords, 0, '.', ' ') }}</div>
        <div class="text-slate-200">Total Records</div>
        @if($bioassayEntity && $bioassayEntity->last_update)
          <div class="text-xs text-slate-300 mt-1">
            Updated: {{ \Carbon\Carbon::parse($bioassayEntity->last_update)->format('Y-m-d') }}
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($allStats))
    <!-- Statistics Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">

      <!-- Totals Card -->
      @if(isset($allStats['bioassay.totals']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Totals</h4>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Records:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.totals']['total_records'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.totals']['total_countries'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Bioassay Names:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.totals']['total_bioassay_names'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Endpoints:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.totals']['total_endpoints'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Determinands:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.totals']['total_determinands'], 0, '.', ' ') }}</span>
            </div>
          </div>
        </div>
      @endif

      <!-- Per Country Card -->
      @if(isset($allStats['bioassay.per_country']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Country</h4>
            <a href="{{ route('bioassay.statistics.perCountry') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.per_country']['total_countries'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topCountry = collect($allStats['bioassay.per_country']['data'])
                ->sortByDesc(fn($item) => $item['count'])
                ->first();
              $topCountryKey = collect($allStats['bioassay.per_country']['data'])
                ->sortByDesc(fn($item) => $item['count'])
                ->keys()
                ->first();
            @endphp
            @if($topCountry)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ $topCountryKey }}<br>{{ number_format($topCountry['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Bioassay Name Card -->
      @if(isset($allStats['bioassay.per_bioassay_name']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Bioassay Name</h4>
            <a href="{{ route('bioassay.statistics.perBioassayName') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Bioassay Names:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.per_bioassay_name']['total_bioassay_names'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topBioassay = collect($allStats['bioassay.per_bioassay_name']['data'])
                ->sortByDesc(fn($item) => $item['count'])
                ->first();
              $topBioassayKey = collect($allStats['bioassay.per_bioassay_name']['data'])
                ->sortByDesc(fn($item) => $item['count'])
                ->keys()
                ->first();
            @endphp
            @if($topBioassay)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topBioassayKey, 25) }}<br>{{ number_format($topBioassay['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Endpoint Card -->
      @if(isset($allStats['bioassay.per_endpoint']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Endpoint</h4>
            <a href="{{ route('bioassay.statistics.perEndpoint') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Endpoints:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.per_endpoint']['total_endpoints'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topEndpoint = collect($allStats['bioassay.per_endpoint']['data'])
                ->sortByDesc(fn($item) => $item['count'])
                ->first();
              $topEndpointKey = collect($allStats['bioassay.per_endpoint']['data'])
                ->sortByDesc(fn($item) => $item['count'])
                ->keys()
                ->first();
            @endphp
            @if($topEndpoint)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topEndpointKey, 25) }}<br>{{ number_format($topEndpoint['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Determinand Card -->
      @if(isset($allStats['bioassay.per_determinand']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Determinand</h4>
            <a href="{{ route('bioassay.statistics.perDeterminand') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Determinands:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.per_determinand']['total_determinands'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topDeterminand = collect($allStats['bioassay.per_determinand']['data'])
                ->sortByDesc(fn($item) => $item['count'])
                ->first();
              $topDeterminandKey = collect($allStats['bioassay.per_determinand']['data'])
                ->sortByDesc(fn($item) => $item['count'])
                ->keys()
                ->first();
            @endphp
            @if($topDeterminand)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topDeterminandKey, 25) }}<br>{{ number_format($topDeterminand['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Year Card -->
      @if(isset($allStats['bioassay.per_year']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Year</h4>
            <a href="{{ route('bioassay.statistics.perYear') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Years:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['bioassay.per_year']['total_years'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topYear = collect($allStats['bioassay.per_year']['data'])
                ->sortByDesc(fn($count) => $count)
                ->first();
              $topYearKey = collect($allStats['bioassay.per_year']['data'])
                ->sortByDesc(fn($count) => $count)
                ->keys()
                ->first();
            @endphp
            @if($topYear)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ $topYearKey }}<br>{{ number_format($topYear, 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

    </div>

  @else
    <!-- No Statistics Available -->
    <div class="text-center py-12">
      <div class="text-gray-500 text-xl mb-4">
        No statistics generated yet
      </div>
      <div class="text-sm text-gray-400 mb-8">
        Generate statistics to see comprehensive data insights and coverage analysis.
      </div>

      @auth
        @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
          <div class="bg-lime-50 border border-lime-200 rounded-lg p-4 max-w-md mx-auto">
            <div class="text-sm text-lime-800 font-medium mb-2">Admin Quick Start:</div>
            <div class="text-sm text-lime-600">
              Use the "Generate All Statistics" button above to create your first statistics overview.
            </div>
          </div>
        @else
          <div class="text-sm text-gray-500">
            Statistics generation is available for administrators only.
          </div>
        @endif
      @else
        <div class="text-sm text-gray-500">
          Please log in to access statistics generation.
        </div>
      @endauth
    </div>
  @endif
@endsection
