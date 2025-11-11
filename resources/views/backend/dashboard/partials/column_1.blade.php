<div class="bg-white overflow-hidden shadow-md rounded-lg">
  <div class="p-4 bg-slate-100 border-b border-slate-200">
    <h3 class="text-lg font-semibold text-gray-800">
      <i class="fas fa-database mr-2 text-gray-600"></i>
      Database Records
    </h3>
    <p class="text-xs text-gray-600 mt-1">Available to all users</p>
  </div>
  <div class="p-4">
    <div class="overflow-hidden bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-4 py-3 bg-slate-50 border-b border-gray-200">
        <div class="grid grid-cols-3 font-medium text-sm text-gray-600">
          <div>Database Name</div>
          <div class="text-right">Records</div>
          <div class="text-right">Searches</div>
        </div>
      </div>

      <div class="divide-y divide-gray-100">
        @foreach ($databaseEntities as $entity)
          @if($entity->is_public || (auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin'))))
            <div class="grid grid-cols-3 px-4 py-3 hover:bg-slate-50">
              <div class="text-gray-800 font-medium">
                {{ $entity->name }}
                @if(!$entity->is_public)
                  <i class="fas fa-lock ml-1 text-gray-500 text-xs"></i>
                @endif
              </div>
              <div class="text-right font-mono text-gray-700">{{ number_format($entity->number_of_records ?? 0) }}</div>
              <div class="text-right font-mono text-gray-700">{{ number_format($entity->query_log_count ?? 0) }}</div>
            </div>
          @endif
        @endforeach
      </div>
    </div>

    <div class="mt-4 text-center">
      <a href="{{ route('home') }}" class="link-lime-text text-sm">
        View All Databases
      </a>
    </div>
  </div>
</div>
