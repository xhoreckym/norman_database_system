<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        
        <form name="searchLiterature" id="searchLiterature" action="{{route('literature.search.search')}}" method="GET">
          
          <div class="p-4 text-gray-900 grid grid-cols-1 gap-4">
            
            <div class="bg-gray-100 p-4">
              <p class="text-gray-700">Search form will be available once the database table is created.</p>
            </div>
            
            <!-- Main Search form -->
            <div class="flex justify-end m-2">
              <a href="{{route('literature.search.filter')}}" class="btn-clear mx-2"> Reset </a>
              <button type="submit" class="btn-submit"> Search
              </button>
            </div>
            
          </div>    
          
        </form>  
      </div>
    </div>
  </div>
</x-app-layout>

