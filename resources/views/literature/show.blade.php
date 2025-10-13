<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            Literature Record Details
          </h1>
          
          <p class="text-gray-700">Record details will be displayed here once the database table is created.</p>
          
        </div>
      </div>
    </div>
  </div>

</x-app-layout>

