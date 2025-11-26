# Statistics Module Development Guide

This guide describes the standard patterns and rules for creating statistics modules in the NORMAN Database System.

## Overview

Statistics modules provide aggregated data insights for database entities. They follow a consistent architecture:
- **Public viewing** - Anyone can view statistics
- **Admin-only generation** - Only `admin` and `super_admin` roles can generate/refresh statistics
- **Stored in database** - Statistics are stored in the `statistics` table as JSON for performance

## Architecture

### Database Storage

Statistics are stored in the `statistics` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `database_entity_id` | bigint | FK to `database_entities.id` |
| `key` | string | Unique identifier for the statistic type (e.g., `arbg.bacteria.per_country`) |
| `meta_data` | jsonb | The actual statistics data |
| `created_at` | timestamp | When the statistic was generated |

### Key Naming Convention

Statistics keys follow the pattern: `{module}.{submodule}.{statistic_type}`

Examples:
- `arbg.bacteria.per_country`
- `arbg.bacteria.per_year`
- `arbg.gene.per_matrix`
- `empodat_suspect.records_by_country`

### Standard Statistic Types

| Type | Description | Data Structure |
|------|-------------|----------------|
| `per_country` | Records grouped by country | `{country_name: {code, count}}` |
| `per_year` | Records grouped by sampling year | `{year: count}` |
| `per_matrix` | Records grouped by sample matrix | `{matrix_name: {id, count}}` |
| `totals` | Summary totals | `{total_records, total_countries, ...}` |

## File Structure

For a new statistics module (e.g., `newmodule`), create:

```
app/Http/Controllers/NewModule/
└── StatisticsController.php

resources/views/newmodule/statistics/
├── layout.blade.php          # Base layout with admin tools
├── index.blade.php           # Overview page
├── per_country.blade.php     # Country detail view
├── per_year.blade.php        # Year detail view
└── per_matrix.blade.php      # Matrix detail view
```

## Controller Implementation

### Required Methods

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\NewModule;

use App\Http\Controllers\Controller;
use App\Models\NewModule\MainModel;
use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Display statistics overview page (PUBLIC)
     */
    public function index()
    {
        $entity = DatabaseEntity::where('code', 'newmodule')->first();
        $allStats = [];

        if ($entity) {
            $statisticKeys = Statistic::where('database_entity_id', $entity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $entity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $entity->number_of_records ?? MainModel::count();

        return view('newmodule.statistics.index', [
            'entity' => $entity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Generate all statistics (ADMIN ONLY - protected by route middleware)
     */
    public function generateAll()
    {
        $this->generateCountryStats();
        $this->generateYearStats();
        $this->generateMatrixStats();
        $this->generateTotalsStats();

        session()->flash('success', 'All statistics generated successfully.');

        return redirect()->back();
    }

    /**
     * Generate country statistics
     */
    public function generateCountryStats(): void
    {
        // Query logic specific to the module's table structure
        $statistics = DB::table('main_table as m')
            ->join('coordinates_table as c', 'm.coordinate_id', '=', 'c.id')
            ->join('country_table as ct', 'c.country_id', '=', 'ct.id')
            ->select(
                'ct.name as country_name',
                'ct.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('c.country_id')
            ->groupBy('ct.name', 'ct.code')
            ->orderBy('record_count', 'desc')
            ->get();

        $countryStats = [];
        foreach ($statistics as $stat) {
            $countryStats[$stat->country_name] = [
                'code' => $stat->country_code,
                'count' => $stat->record_count,
            ];
        }

        $entity = DatabaseEntity::where('code', 'newmodule')->first();

        if ($entity) {
            Statistic::create([
                'database_entity_id' => $entity->id,
                'key' => 'newmodule.per_country',
                'meta_data' => [
                    'data' => $countryStats,
                    'generated_at' => now()->toISOString(),
                    'total_countries' => count($countryStats),
                ],
            ]);
        }
    }

    /**
     * View country statistics (PUBLIC)
     */
    public function perCountry()
    {
        $entity = DatabaseEntity::where('code', 'newmodule')->first();

        if (! $entity) {
            return back()->with('error', 'Database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $entity->id)
            ->where('key', 'newmodule.per_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('newmodule.statistics.per_country', [
                'data' => [],
                'totalCountries' => 0,
                'message' => 'No statistics available. Please generate first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('newmodule.statistics.per_country', [
            'data' => $data['data'],
            'totalCountries' => $data['total_countries'],
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }

    // Similar methods for perYear(), perMatrix()...
}
```

## Routes Configuration

Add routes in `routes/web.php`:

```php
use App\Http\Controllers\NewModule\StatisticsController;

Route::prefix('newmodule')->group(function () {
    // ... existing routes ...

    // Statistics routes
    Route::prefix('statistics')->group(function () {
        // Public routes - anyone can view
        Route::get('/', [StatisticsController::class, 'index'])
            ->name('newmodule.statistics.index');
        Route::get('per-country', [StatisticsController::class, 'perCountry'])
            ->name('newmodule.statistics.perCountry');
        Route::get('per-year', [StatisticsController::class, 'perYear'])
            ->name('newmodule.statistics.perYear');
        Route::get('per-matrix', [StatisticsController::class, 'perMatrix'])
            ->name('newmodule.statistics.perMatrix');

        // Admin-only route for generation
        Route::post('generate', [StatisticsController::class, 'generateAll'])
            ->middleware(['auth', 'role:super_admin|admin'])
            ->name('newmodule.statistics.generate');
    });
});
```

## View Templates

### Layout Template (`layout.blade.php`)

```blade
<x-app-layout>
  <x-slot name="header">
    @include('newmodule.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <!-- Header -->
          <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">
              @yield('page-title', 'Statistics')
            </h2>
            @hasSection('page-subtitle')
              <p class="text-gray-600">@yield('page-subtitle')</p>
            @endif
          </div>

          <!-- Admin Tools (only visible to admins) -->
          @auth
            @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
              <div class="mb-6">
                <div class="bg-amber-50 border border-amber-600 rounded-lg p-4">
                  <h3 class="text-lg font-semibold text-amber-800 mb-4">
                    Generate Statistics (Admin Only)
                  </h3>
                  <form action="{{ route('newmodule.statistics.generate') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-zinc-700 text-white rounded hover:bg-zinc-800 text-sm">
                      Generate All Statistics
                    </button>
                  </form>
                  <div class="mt-3 text-sm text-amber-700">
                    <strong>Note:</strong> This will generate all statistics types.
                  </div>
                </div>
              </div>
            @endif
          @endauth

          <!-- Flash Messages -->
          @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded">
              {{ session('success') }}
            </div>
          @endif

          @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
              {{ session('error') }}
            </div>
          @endif

          <!-- Main Content -->
          <div class="w-full">
            @yield('main-content')
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
```

### Index Template (`index.blade.php`)

```blade
@extends('newmodule.statistics.layout')

@section('page-title', 'Statistics Overview')

@section('main-content')
  <!-- Database Overview Card -->
  <div class="bg-slate-600 text-white rounded-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-2xl font-bold mb-2">Module Name</h3>
        <p class="text-slate-200">Description of the database</p>
      </div>
      <div class="text-right">
        <div class="text-3xl font-bold">{{ number_format($totalRecords, 0, '.', ' ') }}</div>
        <div class="text-slate-200">Total Records</div>
        @if($entity && $entity->last_update)
          <div class="text-xs text-slate-300 mt-1">
            Updated: {{ \Carbon\Carbon::parse($entity->last_update)->format('Y-m-d') }}
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($allStats))
    <!-- Statistics Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

      <!-- Totals Card -->
      @if(isset($allStats['newmodule.totals']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <h4 class="font-semibold text-slate-800 mb-3">Totals</h4>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Records:</span>
              <span class="font-medium">{{ number_format($allStats['newmodule.totals']['total_records'], 0, '.', ' ') }}</span>
            </div>
            <!-- Add more totals as needed -->
          </div>
        </div>
      @endif

      <!-- Per Country Card -->
      @if(isset($allStats['newmodule.per_country']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">By Country</h4>
            <a href="{{ route('newmodule.statistics.perCountry') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-slate-600">Countries:</span>
            <span class="font-medium">{{ $allStats['newmodule.per_country']['total_countries'] }}</span>
          </div>
        </div>
      @endif

      <!-- Similar cards for per_year, per_matrix -->

    </div>
  @else
    <!-- No Statistics Message -->
    <div class="text-center py-12">
      <div class="text-gray-500 text-xl mb-4">No statistics generated yet</div>
      @auth
        @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
          <div class="text-sm text-gray-400">
            Use the "Generate All Statistics" button above.
          </div>
        @endif
      @endauth
    </div>
  @endif
@endsection
```

### Detail View Template (`per_country.blade.php`)

```blade
@extends('newmodule.statistics.layout')

@section('page-title', 'Records by Country')
@section('page-subtitle', 'Number of records per country')

@section('main-content')
  @if(isset($generatedAt))
    <div class="mb-4 text-sm text-gray-600">
      Data generated: {{ \Carbon\Carbon::parse($generatedAt)->format('Y-m-d H:i:s') }}
    </div>
  @endif

  @if(isset($message))
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="text-yellow-800">{{ $message }}</div>
      <a href="{{ route('newmodule.statistics.index') }}" class="text-blue-600 underline text-sm">
        Go back to overview
      </a>
    </div>
  @elseif(empty($data))
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
      <div class="text-gray-600">No data available. Please generate statistics first.</div>
    </div>
  @else
    <!-- Summary Card -->
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-sm text-gray-600">Total Countries</div>
          <div class="text-2xl font-bold">{{ number_format($totalCountries, 0, '.', ' ') }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Total Records</div>
          <div class="text-2xl font-bold">
            {{ number_format(collect($data)->sum(fn($item) => $item['count']), 0, '.', ' ') }}
          </div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Average per Country</div>
          <div class="text-2xl font-bold">
            {{ number_format(collect($data)->sum(fn($item) => $item['count']) / max($totalCountries, 1), 1) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Country</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Records</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          @foreach(collect($data)->sortByDesc(fn($item) => $item['count']) as $country => $info)
            <tr>
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $country }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ $info['code'] }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ number_format($info['count'], 0, '.', ' ') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
      <a href="{{ route('newmodule.statistics.index') }}" class="text-blue-600 hover:text-blue-800 underline text-sm">
        &larr; Back to overview
      </a>
    </div>
  @endif
@endsection
```

## Header Navigation

Add statistics link to the module header (`resources/views/newmodule/header.blade.php`):

```blade
<x-nav-link-header
  :href="route('newmodule.statistics.index')"
  :active="request()->is('newmodule/statistics*')">
  Statistics
</x-nav-link-header>
```

## Module-Specific Considerations

### Country Table Variations

Different modules may use different country tables:

| Module | Country Table | Join Field |
|--------|---------------|------------|
| EMPODAT | `list_countries` | `id` (bigint) |
| ARBG | `arbg_data_country` | `abbreviation` (string) |
| Literature | `list_countries` | `id` (bigint) |

Always check the existing controller code to understand the correct table and join structure.

### Data Structure Variations

Some modules may have additional statistic types:

- `per_substance` - For modules with substance data
- `per_sample_code` - For suspect screening modules
- `per_quality` - For modules with quality ratings

## Checklist for New Statistics Module

1. [ ] Create `StatisticsController.php` with all required methods
2. [ ] Add controller import to `routes/web.php`
3. [ ] Add statistics routes (public views + admin-only generate)
4. [ ] Create `statistics/` view directory with:
   - [ ] `layout.blade.php`
   - [ ] `index.blade.php`
   - [ ] `per_country.blade.php`
   - [ ] `per_year.blade.php`
   - [ ] `per_matrix.blade.php`
5. [ ] Add navigation link to module header
6. [ ] Verify correct table joins for country data
7. [ ] Run `./vendor/bin/pint` on new controller
8. [ ] Test statistics generation as admin
9. [ ] Test statistics viewing as guest/regular user

## Troubleshooting

### Common Errors

**Error: "operator does not exist: character = bigint"**
- Cause: Joining string field to integer field
- Solution: Check country table structure and use correct join

**Error: "Undefined route"**
- Cause: Route not registered or wrong name
- Solution: Run `php artisan route:list | grep statistics` to verify routes

**Error: Statistics not showing after generation**
- Cause: Wrong `database_entity_id` or `key`
- Solution: Check `database_entities` table for correct code and ID
