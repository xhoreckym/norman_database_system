<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        
        <div class="p-6 text-gray-900">
          
          <!-- Main Search form -->
          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 xl:gap-8">
            
            <div class="bg-gray-50 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300">
              <div class="p-4">
                <div class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">
                  Search Category:
                </div>
                <form name="searchAccordingToCategoryForm" id="searchAccordingToCategoryForm" action="{{route('substances.search.search')}}" method="GET" onsubmit="return validateCategorySelection()">
                  <input type="hidden" value="1" name="searchCategory">
                  <input type="hidden" value="1" name="search">
                  
                  <!-- Error message for no category selection -->
                  <div id="categoryError" class="hidden mb-3 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    <div class="flex items-center">
                      <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                      </svg>
                      <span class="font-medium">Please select at least one category to continue.</span>
                    </div>
                  </div>
                  
                  <div class="space-y-1 pr-2">
                    @foreach ($categories as $category)
                    <div class="flex items-center space-x-3 p-0.5 rounded hover:bg-gray-100 transition-colors duration-150">
                      <input type="checkbox" name="categoriesSearch[]" value="{{$category->id}}" id="category_{{$category->id}}" class="w-5 h-5 text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
                      <label for="category_{{$category->id}}" class="text-base text-gray-700 cursor-pointer select-none">
                        {!! preg_replace('/\s*\(/', '&nbsp;(', $category->name_abbreviation, 1) !!}
                      </label>
                    </div>
                    @endforeach
                  </div>
                  
                  <div class="mt-4 pt-3 border-t border-gray-200">
                    <button type="submit" class="btn-submit w-full"> Apply Category Filter </button>
                  </div>
                </form>
              </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300">
              <div class="p-4">
                <div class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">
                  Search according to source in SLE:
                </div>
                
                <form name="searchAccordingToSourceForm" id="searchAccordingToSourceForm" action="{{route('substances.search.search')}}" method="GET">
                  <input type="hidden" value="1" name="searchSource">
                  <div class="w-full">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'sourcesSearch', 'list' => $sourceList,
                      'active_ids' => isset($request->sourcesSearch) ? $request->sourcesSearch : [],
                      ])
                  </div>
                  
                  <div class="mt-4 pt-3 border-t border-gray-200">
                    <button type="submit" class="btn-submit w-full"> Apply Source Filter</button>
                  </div>
                </form>
              </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300">
              <div class="p-4">
                <div class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-gray-200">
                  Interactive search for a specific substance:
                </div>                  
                <div>
                  @livewire('susdat.substance-search')
                </div>
              </div>
            </div>              
            
          </div>        
        </div>
      </div>
    </div>
  </div>

  <script>
    function validateCategorySelection() {
      const checkboxes = document.querySelectorAll('input[name="categoriesSearch[]"]');
      const checkedBoxes = Array.from(checkboxes).filter(checkbox => checkbox.checked);
      const errorDiv = document.getElementById('categoryError');
      
      if (checkedBoxes.length === 0) {
        errorDiv.classList.remove('hidden');
        // Scroll to error message
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
      } else {
        errorDiv.classList.add('hidden');
        return true;
      }
    }

    // Hide error message when user starts selecting categories
    document.addEventListener('DOMContentLoaded', function() {
      const checkboxes = document.querySelectorAll('input[name="categoriesSearch[]"]');
      const errorDiv = document.getElementById('categoryError');
      
      checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
          const checkedBoxes = Array.from(checkboxes).filter(cb => cb.checked);
          if (checkedBoxes.length > 0) {
            errorDiv.classList.add('hidden');
          }
        });
      });
    });
  </script>
</x-app-layout>
  