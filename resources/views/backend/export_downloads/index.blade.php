<x-app-layout>


  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Downloads</h1>
            @if($user)
              <div class="mt-3 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-lg font-medium text-gray-800">User Information</h3>
                <p class="text-xs text-gray-500 mb-3"><em>All times displayed in Central European Time (CET/CEST)</em></p>
                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <span class="text-sm font-medium text-gray-600">Name:</span>
                    <span class="text-sm text-gray-800 ml-2">{{ $user->full_name }}</span>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-600">Email:</span>
                    <span class="text-sm text-gray-800 ml-2">{{ $user->email }}</span>
                  </div>
                  @if($user->organisation)
                  <div>
                    <span class="text-sm font-medium text-gray-600">Organisation:</span>
                    <span class="text-sm text-gray-800 ml-2">{{ $user->organisation }}</span>
                  </div>
                  @endif
                  <div>
                    <span class="text-sm font-medium text-gray-600">Total Downloads:</span>
                    <span class="text-sm text-gray-800 ml-2">{{ $exportDownloads->total() }}</span>
                  </div>
                  @if($exportDownloads->where('status', 'completed')->count() > 0)
                  <div>
                    <span class="text-sm font-medium text-gray-600">Completed Downloads:</span>
                    <span class="text-sm text-green-600 ml-2 font-medium">{{ $exportDownloads->where('status', 'completed')->count() }}</span>
                  </div>
                  @endif
                  @if($exportDownloads->whereNotNull('file_size_bytes')->sum('file_size_bytes') > 0)
                  <div>
                    <span class="text-sm font-medium text-gray-600">Total Downloaded:</span>
                    <span class="text-sm text-gray-800 ml-2">
                      @php
                        $totalBytes = $exportDownloads->whereNotNull('file_size_bytes')->sum('file_size_bytes');
                        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                        $bytes = max($totalBytes, 0);
                        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                        $pow = min($pow, count($units) - 1);
                        $bytes /= (1 << (10 * $pow));
                        echo round($bytes, 2) . ' ' . $units[$pow];
                      @endphp
                    </span>
                  </div>
                  @endif
                </div>
              </div>
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
                    <th class="py-3 px-4 border-b text-left">File Size</th>
                    <th class="py-3 px-4 border-b text-left">Duration</th>
                    <th class="py-3 px-4 border-b text-left">Status</th>
                    <th class="py-3 px-4 border-b text-left">Created (CET)</th>
                    <th class="py-3 px-4 border-b text-left">Actions</th>
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
                        @if($download->formatted_file_size)
                          <span class="text-gray-700 font-medium">{{ $download->formatted_file_size }}</span>
                        @else
                          <span class="text-gray-400">-</span>
                        @endif
                      </td>
                      <td class="py-3 px-4 text-sm">
                        @if($download->duration)
                          <span class="text-gray-700">{{ $download->duration }}</span>
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
                      <td class="py-3 px-4 text-sm text-gray-600">
                        <div>{{ $download->created_at }}</div>
                        @if($download->started_at && $download->completed_at)
                          <div class="text-xs text-gray-500 mt-1">
                            Started: {{ $download->started_at }}<br>
                            Completed: {{ $download->completed_at }}
                          </div>
                        @elseif($download->started_at)
                          <div class="text-xs text-gray-500 mt-1">
                            Started: {{ $download->started_at }}
                          </div>
                        @endif
                      </td>
                      <td class="py-3 px-4 text-sm">
                        @if($download->status === 'completed')
                          @php
                            $downloadRoute = '';
                            try {
                              switch($download->database_key) {
                                case 'empodat':
                                  $downloadRoute = route('csv.download', ['filename' => $download->filename]);
                                  break;
                                case 'sars':
                                  $downloadRoute = route('sars.csv.download', ['filename' => $download->filename]);
                                  break;
                                case 'arbg.bacteria':
                                case 'arbg.gene':
                                case 'arbg':
                                  // ARBG modules might use a general download route if implemented
                                  $downloadRoute = '#'; // TODO: Add ARBG download route when implemented
                                  break;
                                case 'ecotox':
                                case 'ecotox.ecotox':
                                case 'ecotox.ecotox_pnec3':
                                  // Ecotox modules might use a general download route if implemented
                                  $downloadRoute = '#'; // TODO: Add Ecotox download route when implemented
                                  break;
                                case 'passive':
                                case 'indoor':
                                case 'bioassay':
                                  // Other modules might use general download routes if implemented
                                  $downloadRoute = '#'; // TODO: Add download routes when implemented
                                  break;
                                default:
                                  $downloadRoute = '#';
                              }
                            } catch (Exception $e) {
                              $downloadRoute = '#';
                            }
                          @endphp
                          @if($downloadRoute !== '#')
                            <a href="{{ $downloadRoute }}" class="inline-flex items-center px-3 py-1 bg-slate-600 text-white text-xs font-medium rounded hover:bg-slate-700 transition-colors">
                              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                              </svg>
                              Download
                            </a>
                          @else
                            <span class="text-gray-400 text-xs">
                              @if(in_array($download->database_key, ['arbg.bacteria', 'arbg.gene', 'arbg', 'ecotox', 'ecotox.ecotox', 'ecotox.ecotox_pnec3', 'passive', 'indoor', 'bioassay']))
                                Download not implemented
                              @else
                                No download available
                              @endif
                            </span>
                          @endif
                        @else
                          <span class="text-gray-400 text-xs">
                            @if($download->status === 'processing')
                              Processing...
                            @elseif($download->status === 'failed')
                              Failed
                            @else
                              {{ ucfirst($download->status) }}
                            @endif
                          </span>
                        @endif
                      </td>
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
