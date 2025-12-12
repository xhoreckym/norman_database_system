<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">

          @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
              {{ session('success') }}
            </div>
          @endif

          <div class="flex justify-between items-center mb-6">
            <div>
              <a href="{{ route('backend.display.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Back to Modules
              </a>
              <h3 class="text-lg font-medium text-gray-900 mt-2">
                {{ $entity->name }} - Display Sections
              </h3>
            </div>
          </div>

          <p class="text-sm text-gray-600 mb-6">
            Manage display sections for the {{ $entity->name }} module. Drag to reorder sections.
          </p>

          @if($sections->count() > 0)
            <form method="POST" action="{{ route('backend.display.sections.reorder', $entity->code) }}" id="reorder-form">
              @csrf
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Relationship</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Columns</th>
                      <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Visible</th>
                      <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Collapsible</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Style Preview</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200" id="sortable-sections">
                    @foreach($sections as $section)
                      <tr data-id="{{ $section->id }}">
                        <td class="px-4 py-4 cursor-move text-gray-400">
                          <i class="fas fa-grip-vertical"></i>
                          <input type="hidden" name="order[]" value="{{ $section->id }}">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                          <div class="text-sm font-medium text-gray-900">{{ $section->effective_name }}</div>
                          <div class="text-xs text-gray-500 font-mono">{{ $section->code }}</div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                          {{ $section->sectionType?->default_name ?? 'Custom' }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                          {{ $section->relationship ?? '(main record)' }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                          <a href="{{ route('backend.display.columns.index', $section->id) }}"
                             class="text-teal-600 hover:text-teal-900">
                            {{ $section->columns->count() }} columns
                          </a>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                          @if($section->is_visible)
                            <span class="text-green-600"><i class="fas fa-check"></i></span>
                          @else
                            <span class="text-red-600"><i class="fas fa-times"></i></span>
                          @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-center">
                          @if($section->is_collapsible)
                            <span class="text-green-600"><i class="fas fa-check"></i></span>
                            @if($section->is_collapsed_default)
                              <span class="text-xs text-gray-500">(collapsed)</span>
                            @endif
                          @else
                            <span class="text-gray-400">-</span>
                          @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                          <div class="flex items-center space-x-1">
                            <span class="px-2 py-1 text-xs rounded {{ $section->effective_header_bg_class }} {{ $section->effective_header_text_class }}">
                              Header
                            </span>
                            <span class="px-2 py-1 text-xs rounded {{ $section->effective_row_even_class }} {{ $section->effective_row_text_class }}">
                              Even
                            </span>
                            <span class="px-2 py-1 text-xs rounded {{ $section->effective_row_odd_class }} {{ $section->effective_row_text_class }}">
                              Odd
                            </span>
                          </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                          <a href="{{ route('backend.display.sections.edit', $section->id) }}"
                             class="text-slate-600 hover:text-slate-900">
                            Edit
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              <div class="mt-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700">
                  Save Order
                </button>
              </div>
            </form>
          @else
            <div class="text-center py-8">
              <p class="text-gray-500">No sections configured for this module.</p>
              <p class="text-sm text-gray-400 mt-2">Sections are created via database seeders.</p>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const el = document.getElementById('sortable-sections');
      if (el) {
        new Sortable(el, {
          animation: 150,
          handle: '.cursor-move',
          ghostClass: 'bg-gray-100'
        });
      }
    });
  </script>
</x-app-layout>
