@extends('literature.statistics.layout')

@section('page-title', 'Literature Statistics Overview')

@section('main-content')
  <!-- Database Overview Card -->
  <div class="bg-slate-600 text-white rounded-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-2xl font-bold mb-2">Literature Chemical Exposure Database</h3>
        <p class="text-slate-200">Chemical exposure data from scientific literature</p>
      </div>
      <div class="text-right">
        <div class="text-3xl font-bold">{{ number_format($totalRecords, 0, '.', ' ') }}</div>
        <div class="text-slate-200">Total Records</div>
        @if($literatureEntity && $literatureEntity->last_update)
          <div class="text-xs text-slate-300 mt-1">
            Updated: {{ \Carbon\Carbon::parse($literatureEntity->last_update)->format('Y-m-d') }}
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($allStats))
    <!-- Statistics Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">

      <!-- Totals Card -->
      @if(isset($allStats['literature.totals']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Totals</h4>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Records:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.totals']['total_records'] ?? 0, 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.totals']['total_countries'] ?? 0, 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Ecosystems:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.totals']['total_ecosystems'] ?? 0, 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Classes:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.totals']['total_classes'] ?? 0, 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Substances:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.totals']['total_substances'] ?? 0, 0, '.', ' ') }}</span>
            </div>
          </div>
        </div>
      @endif

      <!-- Per Country Card -->
      @if(isset($allStats['literature.per_country']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Country</h4>
            <a href="{{ route('literature.statistics.perCountry') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.per_country']['total_countries'] ?? 0, 0, '.', ' ') }}</span>
            </div>
            @php
              $topCountry = collect($allStats['literature.per_country']['data'] ?? [])
                ->sortByDesc(fn($item) => $item['count'])
                ->first();
              $topCountryKey = collect($allStats['literature.per_country']['data'] ?? [])
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

      <!-- Per Ecosystem Card -->
      @if(isset($allStats['literature.per_ecosystem']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Ecosystem</h4>
            <a href="{{ route('literature.statistics.perEcosystem') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Ecosystems:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.per_ecosystem']['total_ecosystems'] ?? 0, 0, '.', ' ') }}</span>
            </div>
            @php
              $topEcosystem = collect($allStats['literature.per_ecosystem']['data'] ?? [])
                ->sortByDesc(fn($item) => $item['count'])
                ->first();
              $topEcosystemKey = collect($allStats['literature.per_ecosystem']['data'] ?? [])
                ->sortByDesc(fn($item) => $item['count'])
                ->keys()
                ->first();
            @endphp
            @if($topEcosystem)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topEcosystemKey, 25) }}<br>{{ number_format($topEcosystem['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Class Card -->
      @if(isset($allStats['literature.per_class']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Class</h4>
            <a href="{{ route('literature.statistics.perClass') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Classes:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.per_class']['total_classes'] ?? 0, 0, '.', ' ') }}</span>
            </div>
            @php
              $topClass = collect($allStats['literature.per_class']['data'] ?? [])
                ->sortByDesc(fn($item) => $item['count'])
                ->first();
              $topClassKey = collect($allStats['literature.per_class']['data'] ?? [])
                ->sortByDesc(fn($item) => $item['count'])
                ->keys()
                ->first();
            @endphp
            @if($topClass)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topClassKey, 25) }}<br>{{ number_format($topClass['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Substance Card -->
      @if(isset($allStats['literature.per_substance']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Substance</h4>
            <a href="{{ route('literature.statistics.perSubstance') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Substances:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['literature.per_substance']['total_substances'] ?? 0, 0, '.', ' ') }}</span>
            </div>
            @php
              $topSubstance = collect($allStats['literature.per_substance']['data'] ?? [])
                ->sortByDesc(fn($item) => $item['count'])
                ->first();
              $topSubstanceKey = collect($allStats['literature.per_substance']['data'] ?? [])
                ->sortByDesc(fn($item) => $item['count'])
                ->keys()
                ->first();
            @endphp
            @if($topSubstance)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topSubstanceKey, 25) }}<br>{{ number_format($topSubstance['count'], 0, '.', ' ') }} records
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
