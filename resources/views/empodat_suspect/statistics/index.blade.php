@extends('empodat_suspect.statistics.layout')

@section('page-title', 'EMPODAT Suspect Statistics Overview')

@section('main-content')
  <!-- Database Overview Card -->
  <div class="bg-slate-600 text-white rounded-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-2xl font-bold mb-2">EMPODAT Suspect Database</h3>
        <p class="text-slate-200">Suspect screening chemical occurrence data</p>
      </div>
      <div class="text-right">
        <div class="text-3xl font-bold">{{ number_format($totalRecords, 0, '.', ' ') }}</div>
        <div class="text-slate-200">Total Records</div>
        @if($empodatSuspectEntity && $empodatSuspectEntity->last_update)
          <div class="text-xs text-slate-300 mt-1">
            Updated: {{ \Carbon\Carbon::parse($empodatSuspectEntity->last_update)->format('Y-m-d') }}
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($allStats))
    <!-- Statistics Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">

      <!-- Total Substances -->
      @if(isset($allStats['empodat_suspect.total_substances']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Total Substances</h4>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Total Count:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['empodat_suspect.total_substances']['count'], 0, '.', ' ') }}</span>
            </div>
            <div class="text-xs text-slate-500 mt-2">
              Unique chemical substances detected
            </div>
          </div>
        </div>
      @endif

      <!-- Substances by Sample Code -->
      @if(isset($allStats['empodat_suspect.substances_by_sample_code']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Substances by Sample Code</h4>
            <a href="{{ route('empodat_suspect.statistics.substancesBySampleCode') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Sample Codes:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['empodat_suspect.substances_by_sample_code']['total_sample_codes'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topSampleCode = collect($allStats['empodat_suspect.substances_by_sample_code']['data'])
                ->sortByDesc(function($count, $sampleCode) { return $count; })
                ->first();
              $topSampleCodeKey = collect($allStats['empodat_suspect.substances_by_sample_code']['data'])
                ->sortByDesc(function($count, $sampleCode) { return $count; })
                ->keys()
                ->first();
            @endphp
            @if($topSampleCode)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topSampleCodeKey, 20) }}<br>{{ number_format($topSampleCode, 0, '.', ' ') }} substances
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Records by Sample Code -->
      @if(isset($allStats['empodat_suspect.records_by_sample_code']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Sample Code</h4>
            <a href="{{ route('empodat_suspect.statistics.recordsBySampleCode') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Sample Codes:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['empodat_suspect.records_by_sample_code']['total_sample_codes'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topRecordsSampleCode = collect($allStats['empodat_suspect.records_by_sample_code']['data'])
                ->sortByDesc(function($count, $sampleCode) { return $count; })
                ->first();
              $topRecordsSampleCodeKey = collect($allStats['empodat_suspect.records_by_sample_code']['data'])
                ->sortByDesc(function($count, $sampleCode) { return $count; })
                ->keys()
                ->first();
            @endphp
            @if($topRecordsSampleCode)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topRecordsSampleCodeKey, 20) }}<br>{{ number_format($topRecordsSampleCode, 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Substances by Country -->
      @if(isset($allStats['empodat_suspect.substances_by_country']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Substances by Country</h4>
            <a href="{{ route('empodat_suspect.statistics.substancesByCountry') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['empodat_suspect.substances_by_country']['total_countries'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topCountrySubstances = collect($allStats['empodat_suspect.substances_by_country']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->first();
              $topCountrySubstancesKey = collect($allStats['empodat_suspect.substances_by_country']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->keys()
                ->first();
            @endphp
            @if($topCountrySubstances)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ $topCountrySubstancesKey }}<br>{{ number_format($topCountrySubstances['count'], 0, '.', ' ') }} substances
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Records by Country -->
      @if(isset($allStats['empodat_suspect.records_by_country']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Country</h4>
            <a href="{{ route('empodat_suspect.statistics.recordsByCountry') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['empodat_suspect.records_by_country']['total_countries'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topCountryRecords = collect($allStats['empodat_suspect.records_by_country']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->first();
              $topCountryRecordsKey = collect($allStats['empodat_suspect.records_by_country']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->keys()
                ->first();
            @endphp
            @if($topCountryRecords)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ $topCountryRecordsKey }}<br>{{ number_format($topCountryRecords['count'], 0, '.', ' ') }} records
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
        @if(auth()->user()->hasRole('super_admin'))
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-md mx-auto">
            <div class="text-sm text-blue-800 font-medium mb-2">Admin Quick Start:</div>
            <div class="text-sm text-blue-600">
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
