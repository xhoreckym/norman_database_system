<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Header Section --}}
          <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900">
                  Merge History
                </h2>
              </div>
              <a href="{{ route('duplicates.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Duplicates
              </a>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                  <h3 class="font-medium text-blue-800">
                    Canonical Reference System Merge History
                  </h3>
                  <p class="text-blue-700 mt-1">
                    View all substance merge operations and audit trail. Merged substances are preserved but hidden from normal searches.
                  </p>
                </div>
              </div>
            </div>
          </div>

          {{-- Filters Section --}}
          <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Merge History</h3>
            <form method="GET" action="{{ route('duplicates.mergeHistory') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" 
                       id="date_from" 
                       name="date_from" 
                       value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              </div>
              
              <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" 
                       id="date_to" 
                       name="date_to" 
                       value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              </div>
              
              <div>
                <label for="merged_by" class="block text-sm font-medium text-gray-700 mb-1">Merged By</label>
                <select id="merged_by" 
                        name="merged_by" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">All Users</option>
                  @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ ($filters['merged_by'] ?? '') == $user->id ? 'selected' : '' }}>
                      {{ $user->formatted_name }}
                    </option>
                  @endforeach
                </select>
              </div>
              
              <div class="flex items-end">
                <button type="submit" class="btn-submit w-full">
                  Apply Filters
                </button>
              </div>
            </form>
            
            @if(!empty(array_filter($filters)))
              <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="{{ route('duplicates.mergeHistory') }}" class="text-sm text-blue-600 hover:text-blue-800">
                  Clear All Filters
                </a>
              </div>
            @endif
          </div>

          {{-- Merge History Table --}}
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Merged Substance
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Canonical Substance
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Merge Reason
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Merged By
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Merged At
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  @forelse($mergedSubstances as $mergedSubstance)
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                          {{ $mergedSubstance->code }}
                        </div>
                        @if($mergedSubstance->name)
                          <div class="text-sm text-gray-500">
                            {{ $mergedSubstance->name }}
                          </div>
                        @endif
                        <div class="text-xs text-gray-400">
                          ID: {{ $mergedSubstance->id }}
                        </div>
                      </td>
                      
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-green-900">
                          {{ $mergedSubstance->canonical->code ?? 'N/A' }}
                        </div>
                        @if($mergedSubstance->canonical && $mergedSubstance->canonical->name)
                          <div class="text-sm text-green-700">
                            {{ $mergedSubstance->canonical->name }}
                          </div>
                        @endif
                        @if($mergedSubstance->canonical)
                          <div class="text-xs text-green-600">
                            ID: {{ $mergedSubstance->canonical->id }}
                          </div>
                        @endif
                      </td>
                      
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $mergedSubstance->merge_reason }}">
                          {{ $mergedSubstance->merge_reason }}
                        </div>
                      </td>
                      
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          {{ $mergedSubstance->mergedBy->formatted_name ?? 'Unknown' }}
                        </div>
                      </td>
                      
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          {{ $mergedSubstance->merged_at ? $mergedSubstance->merged_at->format('M j, Y') : 'N/A' }}
                        </div>
                        <div class="text-xs text-gray-500">
                          {{ $mergedSubstance->merged_at ? $mergedSubstance->merged_at->format('g:i A') : '' }}
                        </div>
                      </td>
                      
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button type="button" 
                                onclick="showSubstanceDetails({{ $mergedSubstance->id }})"
                                class="text-blue-600 hover:text-blue-900">
                          View Details
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        No merge history found.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          {{-- Pagination --}}
          @if($mergedSubstances->hasPages())
            <div class="mt-6">
              {{ $mergedSubstances->links() }}
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>

  {{-- Substance Details Modal --}}
  <div id="substanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Substance Details</h3>
          <button onclick="closeSubstanceModal()" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div id="substanceDetails" class="text-sm text-gray-600">
          Loading...
        </div>
      </div>
    </div>
  </div>

  <script>
    function showSubstanceDetails(substanceId) {
      // Show modal
      document.getElementById('substanceModal').classList.remove('hidden');
      
      // Load substance details via AJAX (you can implement this endpoint)
      document.getElementById('substanceDetails').innerHTML = 'Substance ID: ' + substanceId + '<br>Details would be loaded here...';
    }
    
    function closeSubstanceModal() {
      document.getElementById('substanceModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('substanceModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeSubstanceModal();
      }
    });
  </script>
</x-app-layout>
