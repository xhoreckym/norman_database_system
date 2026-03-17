<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <form action="{{ route('substances.update', $substance->id) }}" method="POST">
            @csrf
            @method('PUT')

            <table class="table-auto w-full border-separate border-spacing-1 text-xs">
              @foreach ($editables as $value)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold">{{ $value }}</td>
                  <td>
                    @if (substr($value, 0, 8) === 'metadata')
                      @php
                        try {
                          // Handle both string and array values for metadata
                          $metadataValue = $substance->$value ?? null;
                          if (is_string($metadataValue)) {
                            $metadataArray = json_decode($metadataValue, true) ?? [];
                          } else {
                            $metadataArray = is_array($metadataValue) ? $metadataValue : [];
                          }
                          
                          // Additional safety check - ensure we always have an array
                          if (!is_array($metadataArray)) {
                            $metadataArray = [];
                          }
                        } catch (\Exception $e) {
                          // Fallback to empty array if there's any error
                          $metadataArray = [];
                        }
                      @endphp
                      @if (!empty($metadataArray))
                        @foreach ($metadataArray as $keyInner => $valueInner)
                          <span class="block py-1">
                            <span class="font-bold">{{ $keyInner }}:</span>
                            <input type="text" name="{{ $value }}[{{ $keyInner }}]"
                              value="{{ $valueInner }}" class="form-text-small text-sm">
                          </span>
                        @endforeach
                      @endif
                      <span class="block py-1">
                        <input type="text" name="{{ $value }}_new_key[]" placeholder="key"
                          class="form-text-small text-sm w-1/4 inline-block">
                        <input type="text" name="{{ $value }}_new_value[]" placeholder="value"
                          class="form-text-small text-sm w-2/3 inline-block">
                      </span>
                    @elseif($value === 'code')
                      <span class="font-mono text-sm p-1">NS{{ $substance->$value }}</span>
                    @else
                      <input type="text" name="{{ $value }}" value="{{ $substance->$value }}"
                        class="form-text">
                    @endif
                  </td>
                </tr>
              @endforeach
            </table>

            <div class="flex justify-end m-2">
              <button type="submit" class="btn-submit"> Update Substance
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
