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
                <form name="searchAccordingToCategoryForm" id="searchAccordingToCategoryForm" action="{{route('substances.search')}}" method="GET">
                  <input type="hidden" value="1" name="searchCategory">
                  <input type="hidden" value="1" name="search">
                  
                  <div class="space-y-2 pr-2">
                    @foreach ($categories as $category)
                    <div class="flex items-center space-x-2 p-2 rounded hover:bg-gray-100 transition-colors duration-150">
                      <input type="checkbox" name="categoriesSearch[]" value="{{$category->id}}" id="category_{{$category->id}}" class="w-4 h-4 text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
                      <label for="category_{{$category->id}}" class="text-sm text-gray-700">
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
                  Search according to source:
                </div>
                <form name="searchAccordingToSourceForm" id="searchAccordingToSourceForm" action="{{route('substances.search')}}" method="GET">
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
</x-app-layout>
  