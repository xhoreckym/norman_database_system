<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>


  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <div class="flex items-center space-x-4 mb-4">
            <a href="{{ route('literature.search.filter') }}">
              <button type="submit" class="btn-submit">Refine Search</button>
            </a>

            <div class="flex items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
              <span class="text-gray-700">Search results will be displayed here once the database table is created.</span>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

</x-app-layout>

