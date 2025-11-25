<div class="flex items-center">
    <span wire:init="init">Number of matched records:</span>

    {{-- Loading spinner --}}
    <span wire:loading class="flex items-center ml-1">
        <svg class="w-4 h-4 text-gray-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span class="ml-1 text-gray-500">
            @if (!is_null($loadingMessage))
                {{ $loadingMessage }}
            @else
                Processing...
            @endif
        </span>
    </span>

    {{-- Result --}}
    <span wire:loading.remove class="ml-1 mr-1 font-bold">
        @if (is_numeric($countResult))
            {{ number_format($countResult, 0, ".", " ") }}
        @else
            {{ $countResult }}
        @endif
    </span>

    <span wire:loading.remove>
        of {{ number_format($resultsCount, 0, ".", " ") }}
        @if (is_numeric($countResult) && $resultsCount > 0)
            @if ($countResult/$resultsCount*100 < 0.01)
                (&le; 0.01%)
            @else
                ({{ number_format($countResult/$resultsCount*100, 2, ".", " ") }}%)
            @endif
        @endif
    </span>
</div>
