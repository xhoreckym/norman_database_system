<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        <div class="px-6 py-4">
          <h1 class="text-3xl font-bold text-gray-900 mb-8">Quality Target</h1>
          
          <div class="text-center text-gray-500 py-8">
            <p>Quality Target module content will be specified later</p>
          </div>
          
          <div class="flex justify-center space-x-4">
            <a href="{{ route('ecotox.quality.search.filter') }}" class="btn-submit">
              Search Quality Target
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
