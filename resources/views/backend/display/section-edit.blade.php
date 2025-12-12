<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">

          @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
              <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-6">
            <a href="{{ route('backend.display.sections', $section->databaseEntity->code) }}" class="text-sm text-gray-500 hover:text-gray-700">
              <i class="fas fa-arrow-left mr-1"></i> Back to Sections
            </a>
            <h3 class="text-lg font-medium text-gray-900 mt-2">
              Edit Section: {{ $section->effective_name }}
            </h3>
            <p class="text-sm text-gray-500">
              Module: {{ $section->databaseEntity->name }} | Code: <span class="font-mono">{{ $section->code }}</span>
            </p>
          </div>

          <form method="POST" action="{{ route('backend.display.sections.update', $section->id) }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">

              {{-- Basic Settings --}}
              <div class="border-b border-gray-200 pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Basic Settings</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Section Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $section->name) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                           placeholder="Leave empty to use type default">
                    <p class="mt-1 text-xs text-gray-500">Override the section type's default name</p>
                  </div>

                  <div>
                    <label for="section_type_id" class="block text-sm font-medium text-gray-700">Section Type</label>
                    <select name="section_type_id" id="section_type_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                      <option value="">-- Custom (no type) --</option>
                      @foreach($sectionTypes as $type)
                        <option value="{{ $type->id }}" {{ old('section_type_id', $section->section_type_id) == $type->id ? 'selected' : '' }}>
                          {{ $type->default_name }} ({{ $type->code }})
                        </option>
                      @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Determines default styling</p>
                  </div>

                  <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700">Display Order</label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $section->display_order) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                           min="0">
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Relationship</label>
                    <input type="text" value="{{ $section->relationship ?? '(main record)' }}" disabled
                           class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Cannot be changed (set via seeder)</p>
                  </div>
                </div>
              </div>

              {{-- Visibility Settings --}}
              <div class="border-b border-gray-200 pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Visibility Settings</h4>

                <div class="space-y-4">
                  <div class="flex items-center">
                    <input type="checkbox" name="is_visible" id="is_visible" value="1"
                           {{ old('is_visible', $section->is_visible) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <label for="is_visible" class="ml-2 block text-sm text-gray-700">
                      Visible in show view
                    </label>
                  </div>

                  <div class="flex items-center">
                    <input type="checkbox" name="is_collapsible" id="is_collapsible" value="1"
                           {{ old('is_collapsible', $section->is_collapsible) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <label for="is_collapsible" class="ml-2 block text-sm text-gray-700">
                      Allow section to be collapsed
                    </label>
                  </div>

                  <div class="flex items-center ml-6">
                    <input type="checkbox" name="is_collapsed_default" id="is_collapsed_default" value="1"
                           {{ old('is_collapsed_default', $section->is_collapsed_default) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <label for="is_collapsed_default" class="ml-2 block text-sm text-gray-700">
                      Collapsed by default
                    </label>
                  </div>
                </div>
              </div>

              {{-- Style Overrides --}}
              <div class="pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Style Overrides</h4>
                <p class="text-sm text-gray-500 mb-4">Leave empty to use section type defaults. Enter Tailwind CSS classes.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="header_bg_class" class="block text-sm font-medium text-gray-700">Header Background</label>
                    <input type="text" name="header_bg_class" id="header_bg_class" value="{{ old('header_bg_class', $section->header_bg_class) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm font-mono"
                           placeholder="e.g. bg-teal-600">
                    <p class="mt-1 text-xs text-gray-500">Current: {{ $section->effective_header_bg_class }}</p>
                  </div>

                  <div>
                    <label for="header_text_class" class="block text-sm font-medium text-gray-700">Header Text</label>
                    <input type="text" name="header_text_class" id="header_text_class" value="{{ old('header_text_class', $section->header_text_class) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm font-mono"
                           placeholder="e.g. text-white">
                    <p class="mt-1 text-xs text-gray-500">Current: {{ $section->effective_header_text_class }}</p>
                  </div>

                  <div>
                    <label for="row_even_class" class="block text-sm font-medium text-gray-700">Even Row Background</label>
                    <input type="text" name="row_even_class" id="row_even_class" value="{{ old('row_even_class', $section->row_even_class) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm font-mono"
                           placeholder="e.g. bg-teal-50">
                    <p class="mt-1 text-xs text-gray-500">Current: {{ $section->effective_row_even_class }}</p>
                  </div>

                  <div>
                    <label for="row_odd_class" class="block text-sm font-medium text-gray-700">Odd Row Background</label>
                    <input type="text" name="row_odd_class" id="row_odd_class" value="{{ old('row_odd_class', $section->row_odd_class) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm font-mono"
                           placeholder="e.g. bg-teal-100">
                    <p class="mt-1 text-xs text-gray-500">Current: {{ $section->effective_row_odd_class }}</p>
                  </div>

                  <div>
                    <label for="row_text_class" class="block text-sm font-medium text-gray-700">Row Text</label>
                    <input type="text" name="row_text_class" id="row_text_class" value="{{ old('row_text_class', $section->row_text_class) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm font-mono"
                           placeholder="e.g. text-teal-900">
                    <p class="mt-1 text-xs text-gray-500">Current: {{ $section->effective_row_text_class }}</p>
                  </div>
                </div>

                {{-- Style Preview --}}
                <div class="mt-6">
                  <h5 class="text-sm font-medium text-gray-700 mb-2">Current Style Preview</h5>
                  <div class="border rounded-md overflow-hidden">
                    <div class="p-2 text-center font-bold {{ $section->effective_header_bg_class }} {{ $section->effective_header_text_class }}">
                      {{ $section->effective_name }}
                    </div>
                    <div class="p-2 {{ $section->effective_row_even_class }} {{ $section->effective_row_text_class }}">
                      Even row example
                    </div>
                    <div class="p-2 {{ $section->effective_row_odd_class }} {{ $section->effective_row_text_class }}">
                      Odd row example
                    </div>
                  </div>
                </div>
              </div>

              {{-- Submit --}}
              <div class="flex justify-end space-x-3">
                <a href="{{ route('backend.display.sections', $section->databaseEntity->code) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50">
                  Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700">
                  Save Changes
                </button>
              </div>

            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
