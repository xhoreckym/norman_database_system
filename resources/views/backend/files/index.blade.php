<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- File Actions -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Files Management</h2>
            <div class="flex space-x-3">
              <a href="{{ route('files.create') }}" class="btn-create">
                Upload New File
              </a>
            </div>
          </div>

          <!-- Search and Filter Form -->
          <form method="GET" action="{{ route('files.index') }}" id="filterForm" class="mb-6">
            <div class="flex justify-between items-center">
              <div class="flex space-x-4 flex-1">
                <div class="w-32">
                  <label for="perPage" class="block text-sm font-medium text-gray-700">Show</label>
                  <select name="per_page" id="perPage" onchange="document.getElementById('filterForm').submit()" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                  </select>
                </div>

                <div class="flex-1">
                  <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                  <input type="text"
                    name="search"
                    id="search"
                    value="{{ $search }}"
                    placeholder="Search by name or description..."
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                </div>

                <div class="flex items-end">
                  <button type="submit" class="btn-submit px-4 py-2">
                    Search
                  </button>
                  @if($search || $perPage != 25)
                    <a href="{{ route('files.index') }}" class="ml-2 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                      Clear
                    </a>
                  @endif
                </div>
              </div>
            </div>

            <!-- Hidden fields for sort -->
            <input type="hidden" name="sort" id="sortField" value="{{ $sort }}">
            <input type="hidden" name="direction" id="sortDirection" value="{{ $direction }}">
          </form>

          <!-- Files Table -->
          <div class="overflow-x-auto">
            <table class="table-standard w-full">
              <thead>
                <tr class="bg-gray-600 text-white">
                  @foreach ($columns as $column)
                  <th class="py-2 px-4 text-left">
                    <a href="javascript:void(0)" onclick="sortBy('{{ $column }}')" class="flex items-center cursor-pointer hover:text-gray-200">
                      <span>{{ Str::title(str_replace('_', ' ', $column)) }}</span>
                      @if($sort === $column)
                        <span class="ml-1">
                          @if($direction === 'asc')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                          @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                          @endif
                        </span>
                      @endif
                    </a>
                  </th>
                  @endforeach
                  <th class="py-2 px-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($files as $index => $file)
                  <tr class="hover:bg-slate-300 transition {{ $index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200' }}">
                    <td class="py-2 px-4">
                      <span class="font-mono text-xs font-semibold text-gray-800 bg-gray-200 px-2 py-1 rounded">{{ $file->id }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <div class="font-medium text-gray-900">{{ $file->name ?: $file->original_name ?: 'N/A' }}</div>
                      @if($file->description)
                        <div class="text-sm text-gray-600">{{ Str::limit($file->description, 60) }}</div>
                      @endif
                    </td>
                    <td class="py-2 px-4">
                      @if($file->project)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $file->project->name }}</span>
                      @else
                        <span class="text-gray-400">No Project</span>
                      @endif
                    </td>
                    <td class="py-2 px-4">
                      @if($file->databaseEntity)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $file->databaseEntity->name }}</span>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-2 px-4">
                      @if($file->template)
                        <span class="text-sm text-gray-900">{{ $file->template->name }}</span>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-2 px-4">
                      @if($file->file_size)
                        <span class="text-sm text-gray-900">
                          @php
                            $bytes = $file->file_size;
                            $units = ['Bytes', 'KB', 'MB', 'GB'];
                            if ($bytes == 0) {
                              echo '0 Bytes';
                            } else {
                              $i = floor(log($bytes) / log(1024));
                              echo round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
                            }
                          @endphp
                        </span>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-2 px-4">
                      @if($file->uploader)
                        <div>
                          <div class="text-sm text-gray-900">{{ $file->uploader->name }}</div>
                          <div class="text-xs text-gray-500">{{ $file->uploader->email }}</div>
                        </div>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-2 px-4">
                      @if($file->uploaded_at)
                        <span class="text-sm text-gray-900">{{ $file->uploaded_at->format('Y-m-d G:i:s') }}</span>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-2 px-4 text-center">
                      <div class="flex justify-center space-x-2">
                        <a href="{{ route('files.show', $file) }}" class="text-gray-600 hover:text-gray-900 text-sm px-2 py-1">
                          View
                        </a>
                        <a href="{{ route('files.edit', $file) }}" class="text-gray-600 hover:text-gray-900 text-sm px-2 py-1">
                          Edit
                        </a>
                        @if($file->file_path && Storage::disk('public')->exists($file->file_path))
                          <a href="{{ route('files.download', $file) }}" class="btn-submit text-xs px-2 py-1">
                            Download
                          </a>
                        @elseif($file->file_path)
                          <span class="text-xs px-2 py-1 text-red-600 italic">
                            File not found
                          </span>
                        @else
                          <span class="text-xs px-2 py-1 text-gray-400 italic">
                            No file
                          </span>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr class="bg-slate-100">
                    <td colspan="{{ count($columns) + 1 }}" class="py-6 px-4 text-center text-gray-500">
                      <p class="text-base">No files found</p>
                      <p class="text-sm mt-1">Try adjusting your search to find what you're looking for.</p>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="mt-4">
            {{ $files->links('pagination::tailwind') }}
          </div>

          <div class="mt-2 text-sm text-gray-700 text-center">
            @if($files->total() > 0)
              Showing {{ $files->firstItem() }} to {{ $files->lastItem() }} of {{ $files->total() }} files
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    // Debounced search functionality
    let searchTimeout;
    const searchInput = document.getElementById('search');
    const filterForm = document.getElementById('filterForm');

    if (searchInput) {
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
          filterForm.submit();
        }, 500); // 500ms debounce delay
      });
    }

    // Sorting functionality
    function sortBy(column) {
      const sortField = document.getElementById('sortField');
      const sortDirection = document.getElementById('sortDirection');
      const currentSort = sortField.value;
      const currentDirection = sortDirection.value;

      if (currentSort === column) {
        // Toggle direction if same column
        sortDirection.value = currentDirection === 'asc' ? 'desc' : 'asc';
      } else {
        // Default to descending for new column
        sortField.value = column;
        sortDirection.value = 'desc';
      }

      filterForm.submit();
    }
  </script>
  @endpush
</x-app-layout>
