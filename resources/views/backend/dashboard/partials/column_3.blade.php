@role('super_admin')
  <div class="bg-white overflow-hidden shadow-md rounded-lg">
    <div class="p-4 bg-gray-800 border-b border-gray-700">
      <h3 class="text-lg font-semibold text-white">
        <i class="fas fa-tools mr-2 text-gray-300"></i>
        Super Admin Tools
      </h3>
      <p class="text-xs text-gray-300 mt-1">Super Admin only</p>
    </div>
    <div class="p-4">
      <!-- Admin Process Groups -->
      <div class="space-y-6">
        @foreach ($adminProcessGroups as $group)
          <div>
            <h4 class="font-semibold text-sm uppercase text-gray-500 mb-3">{{ $group['name'] }}</h4>
            <div class="flex flex-wrap gap-2">
              @foreach ($group['processes'] as $process)
                <form action="{{ route($process['route']) }}" method="{{ $process['method'] }}">
                  @csrf
                  <button type="submit" class="btn-submit text-xs">{{ $process['name'] }}</button>
                </form>
              @endforeach
            </div>
          </div>
        @endforeach

        <!-- Database Management Section -->
        <div>
          <h4 class="font-semibold text-sm uppercase text-gray-500 mb-3">Database Management</h4>
          <div class="flex flex-wrap gap-2">
            <a href="{{ route('ecotox.unique.search.substances') }}" class="btn-submit text-xs">
              <i class="fas fa-sync-alt mr-1"></i>
              Sync Ecotox Substances
            </a>
            <a href="{{ route('ecotox.unique.search.substances.pnec3') }}" class="btn-submit text-xs">
              <i class="fas fa-sync-alt mr-1"></i>
              Sync PNEC3 Substances
            </a>
            <a href="{{ route('ecotox.countAll') }}" class="btn-submit text-xs">
              <i class="fas fa-calculator mr-1"></i>
              Update Record Counts
            </a>
            <form action="{{ route('factsheets.statistics.populate-all') }}" method="POST" class="inline">
              @csrf
              <button type="submit" class="btn-submit text-xs">
                <i class="fas fa-chart-bar mr-1"></i>
                Populate Factsheet Statistics
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Admin Actions -->
      <div class="mt-8 pt-4 border-t border-gray-200">
        <h4 class="font-semibold text-sm uppercase text-gray-500 mb-3">Admin Actions</h4>
        <div class="grid grid-cols-1 gap-3">
          <a href="{{ route('querylog.index') }}"
            class="flex items-center p-3 bg-gray-50 rounded-md hover:bg-gray-100 transition">
            <i class="fas fa-clipboard-list text-gray-600 mr-3"></i>
            <span class="text-sm font-medium">View System Logs</span>
          </a>
          <a href="{{ route('templates.create') }}"
            class="flex items-center p-3 bg-stone-50 rounded-md hover:bg-stone-100 transition">
            <i class="fas fa-plus text-gray-600 mr-3"></i>
            <span class="text-sm font-medium">Create Template</span>
          </a>
          <a href="{{ route('files.create') }}"
            class="flex items-center p-3 bg-stone-50 rounded-md hover:bg-stone-100 transition">
            <i class="fas fa-upload text-gray-600 mr-3"></i>
            <span class="text-sm font-medium">Upload File</span>
          </a>
        </div>
      </div>
    </div>
  </div>
@endrole
