<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    
      {{-- @include('_t.errors') --}}

      <div class="bg-white shadow-lg sm:rounded-lg">
        
        <div class="p-6 text-gray-900">
        
          <div class="w-full">
            <div class="flex justify-between items-center">
              <span class="text-xl font-bold">Substance Details</span>
              <a class="link-edit" href="{{route('substances.edit', $substance->id)}}">
                Edit
              </a>
          </div>

          <table class="table-auto w-full border-separate border-spacing-1 text-xs">
            @foreach ($substance->toArray() as $key => $value)
            <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
              <td class="p-1 font-bold">{{$key}}</td>
              @if (substr($key, 0, 8) == 'metadata')
              @php
              // Handle both string and array values for metadata
              if (is_string($value)) {
                $decodedJson = json_decode($value, true);
              } else {
                $decodedJson = $value;
              }
              
              // Ensure we have an array to work with
              if (is_array($decodedJson)) {
                $prettyJson = json_encode($decodedJson, JSON_PRETTY_PRINT);
                $escapedJson = htmlspecialchars($prettyJson, ENT_QUOTES, 'UTF-8');
              } else {
                $decodedJson = [];
              }
              @endphp
              <td class="p-1">
                @if (is_array($decodedJson) && !empty($decodedJson))
                  @foreach ($decodedJson as $keyInner => $valueInner)
                    <span class="block py-1"><span class="font-bold">{{$keyInner}}:</span> {{$valueInner}}</span>
                  @endforeach
                @else
                  <span class="text-gray-500">No metadata available</span>
                @endif
              </td>
              @else
              <td>{{$value}}</td>
              @endif
              
            </tr>
            @endforeach
          </table>
          
          
        </div>
      </div>
    </div>
  </div>
</x-app-layout>