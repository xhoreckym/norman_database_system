<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        <div class="p-6 text-gray-900">

          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            EMPODAT Suspect - Edit Record
          </h1>

          <p class="text-gray-700 leading-relaxed mb-4">
            Record ID: {{ $id ?? 'N/A' }}
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            Edit form will be displayed here.
          </p>

        </div>
      </div>
    </div>
  </div>

</x-app-layout>
