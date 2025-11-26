@extends('bioassay.statistics.layout')

@section('page-title', 'Records by Bioassay Name')
@section('page-subtitle', 'Number of records per bioassay name')

@section('main-content')
  @if(isset($generatedAt))
    <div class="mb-4 text-sm text-gray-600">
      Data generated: {{ \Carbon\Carbon::parse($generatedAt)->format('Y-m-d H:i:s') }}
    </div>
  @endif

  @if(isset($message))
    <!-- No Data Message -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="text-yellow-800">{{ $message }}</div>
      <a href="{{ route('bioassay.statistics.index') }}" class="text-lime-600 hover:text-lime-800 underline text-sm">
        Go back to statistics overview
      </a>
    </div>
  @elseif(empty($data))
    <!-- Empty Data -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
      <div class="text-gray-600 text-lg mb-2">No data available.</div>
      <div class="text-sm text-gray-500">Please generate statistics first.</div>
    </div>
  @else
    <!-- Summary Card -->
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-sm text-gray-600">Total Bioassay Names</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($totalBioassayNames, 0, '.', ' ') }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Total Records</div>
          <div class="text-2xl font-bold text-gray-900">
            {{ number_format(collect($data)->sum(fn($item) => $item['count']), 0, '.', ' ') }}
          </div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Average Records per Bioassay</div>
          <div class="text-2xl font-bold text-gray-900">
            {{ number_format(collect($data)->sum(fn($item) => $item['count']) / max($totalBioassayNames, 1), 1, '.', ' ') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Bioassay Name
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Number of Records
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            @foreach(collect($data)->sortByDesc(fn($item) => $item['count']) as $bioassayName => $info)
              <tr>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                  {{ $bioassayName }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ number_format($info['count'], 0, '.', ' ') }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
      <a href="{{ route('bioassay.statistics.index') }}" class="text-lime-600 hover:text-lime-800 underline text-sm">
        &larr; Back to statistics overview
      </a>
    </div>
  @endif
@endsection
