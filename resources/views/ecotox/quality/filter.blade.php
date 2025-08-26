<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        <div class="px-6 py-4">
          <h1 class="text-3xl font-bold text-gray-900 mb-8">Search Quality Target</h1>
          
          <form method="GET" action="{{ route('ecotox.quality.search.search') }}" class="space-y-6">
            @csrf
            
            <!-- Search Form Content will be added here -->
            <div class="text-center text-gray-500 py-8">
              <p>Search form content will be specified later</p>
            </div>
            
            <div class="flex justify-center">
              <button type="submit" class="btn-submit">
                Search
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
