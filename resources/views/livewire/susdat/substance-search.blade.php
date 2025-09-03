<div>
  <form name="searchSpecificSubstanceForm" id="searchSpecificSubstanceForm" action="{{route('substances.search.search')}}" method="GET">
    <input type="hidden" value="1" name="searchSubstance">
    <input type="text" wire:model.live.debounce.300ms="search" name="searchSubstanceString" id="searchSubstanceString" class="w-full px-4 py-2 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-lime-500 focus:border-transparent transition duration-200 ease-in-out">
    <div class="mt-4">
      <span class="text-gray-700">Search by:</span>
      <div class="mt-2">
        <label class="inline-flex items-center">
          <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="cas_number" class="text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
          <span class="ml-2">CAS</span>
        </label>
        <label class="inline-flex items-center ml-6">
          <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="name" checked class="text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
          <span class="ml-2">Name</span>
        </label>
        <label class="inline-flex items-center ml-6">
          <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="stdinchikey" class="text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
          <span class="ml-2">StdInChIKey</span>
        </label>
      </div>
    </div>
    <span class="text-gray-500 text-sm">the search is limited to 50 substances</span>
    <span class="text-gray-500 text-sm">search type: {{$searchType}}</span>
    <div>
      @foreach ($results as $result)
      <div class="block p-1">
        <span>
          <input type="checkbox" name="substancesSearch[]" value="{{$result->id}}" class="text-lime-600 border-gray-300 rounded focus:ring-lime-500 focus:ring-2">
        </span>
        <span class="ml-1">
          {{$result->name}} <span class="text-sm"> ({{$result->cas_number}} | {{$result->stdinchikey}})</span>
        </span>
      </div>
      @endforeach
      @if($resultsAvailable == true)
      @if($results->count() > 0)
      <div class="flex justify-end m-2">
        <button type="submit" class="btn-submit"> Apply Substance Filter</button>
      </div>
      @else
      <div class="flex justify-end m-2">
        <span class="text-red-500"> No substances found</span>
      </div>
      @endif
      @endif
    </div>
  </form>
</div>
