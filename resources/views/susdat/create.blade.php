<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <h2 class="text-xl font-semibold mb-4">Create New Substance</h2>

          @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md">
              <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('substances.store') }}" method="POST">
            @csrf

            <table class="table-auto w-full border-separate border-spacing-1 text-xs">
              @foreach ($editables as $value)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold">{{ $value }}</td>
                  <td>
                    @if (substr($value, 0, 8) === 'metadata')
                      <span class="block py-1">
                        <input type="text" name="{{ $value }}_new_key[]" placeholder="key"
                          class="form-text-small text-sm w-1/4 inline-block">
                        <input type="text" name="{{ $value }}_new_value[]" placeholder="value"
                          class="form-text-small text-sm w-2/3 inline-block">
                      </span>
                    @elseif($value === 'code')
                      <span class="font-mono text-sm p-1">NS{{ $substance->$value }}</span>
                      <input type="hidden" name="code" value="{{ $substance->$value }}">
                    @else
                      <input type="text" name="{{ $value }}" value="{{ old($value, $substance->$value) }}"
                        class="form-text">
                    @endif
                  </td>
                </tr>
              @endforeach
            </table>

            <div class="flex justify-end m-2">
              <button type="submit" class="btn-submit">Create Substance</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
