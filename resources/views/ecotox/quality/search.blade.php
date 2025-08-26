<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        <div class="px-6 py-4">
          <h1 class="text-3xl font-bold text-gray-900 mb-8">Quality Target Search Results</h1>
          
          <!-- Search Results Content will be added here -->
          <div class="text-center text-gray-500 py-8">
            <p>Search results content will be specified later</p>
          </div>
        </div>
      </div>
    </div>
    
    <div class="mt-6 flex justify-center">
      <a href="{{ route('ecotox.quality.search.filter') }}" class="btn-create">
        New Search
      </a>
    </div>
  </div>
</x-app-layout>
