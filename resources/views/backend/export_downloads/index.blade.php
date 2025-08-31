<x-app-layout>
  <x-slot name="header">
    <div class="px-4 sm:px-6 lg:px-8">
      <span class="mr-12 font-bold text-lime-700">
        Export Downloads
      </span>
    </div>
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Export Downloads</h1>
            @if($exportDownloads->first() && $exportDownloads->first()->user)
              <p class="text-gray-600 mt-2">Downloads for user: <span class="font-semibold">{{ $exportDownloads->first()->user->name }}</span></p>
            @endif
          </div>

          @if($exportDownloads->count() > 0)
            <div class="overflow-x-auto">
              <table class="min-w-full bg-white border border-gray-200">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-3 px-4 border-b text-left">ID</th>
                    <th class="py-3 px-4 border-b text-left">Filename</th>
                    <th class="py-3 px-4 border-b text-left">Format</th>
                    <th class="py-3 px-4 border-b text-left">Database</th>
                    <th class="py-3 px-4 border-b text-left">Records</th>
                    <th class="py-3 px-4 border-b text-left">Status</th>
                    <th class="py-3 px-4 border-b text-left">Created</th>
                    <th class="py-3 px-4 border-b text-left">IP Address</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($exportDownloads as $download)
                    <tr class="hover:bg-gray-50 border-b">
                      <td class="py-3 px-4 text-sm">{{ $download->id }}</td>
                      <td class="py-3 px-4 text-sm font-medium">
                        <span class="text-gray-800">{{ $download->filename }}</span>
                      </td>
                      <td class="py-3 px-4 text-sm">
                        <span class="bg-gray-100 px-2 py-1 rounded text-xs uppercase">{{ $download->format }}</span>
                      </td>
                      <td class="py-3 px-4 text-sm">{{ $download->database_key }}</td>
                      <td class="py-3 px-4 text-sm">
                        @if($download->record_count)
                          {{ number_format($download->record_count) }}
                        @else
                          <span class="text-gray-400">-</span>
                        @endif
                      </td>
                      <td class="py-3 px-4 text-sm">
                        @if($download->status === 'completed')
                          <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Completed</span>
                        @elseif($download->status === 'processing')
                          <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Processing</span>
                        @elseif($download->status === 'failed')
                          <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Failed</span>
                        @else
                          <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">{{ ucfirst($download->status) }}</span>
                        @endif
                      </td>
                      <td class="py-3 px-4 text-sm text-gray-600">{{ $download->created_at }}</td>
                      <td class="py-3 px-4 text-sm text-gray-500">{{ $download->ip_address ?: '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="mt-6">
              {{ $exportDownloads->appends(['user_id' => $userId])->links() }}
            </div>
          @else
            <div class="text-center py-8">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No downloads found</h3>
                <p class="mt-1 text-sm text-gray-500">No export downloads were found for this user.</p>
              </div>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
