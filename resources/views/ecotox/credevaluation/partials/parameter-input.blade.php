@php
    $inputType = $parameter->ecotoxConfig->input_type ?? 'text';
    $columnName = $parameter->ecotoxConfig->column_name ?? '';
    $parameterId = $parameter->id;
@endphp

@switch($inputType)
    @case('text')
        <input type="text" 
               name="parameter_{{ $parameterId }}" 
               class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
               placeholder="Enter {{ $columnName }}">
        @break
        
    @case('numeric')
        <input type="number" 
               name="parameter_{{ $parameterId }}" 
               step="any"
               class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
               placeholder="Enter {{ $columnName }}">
        @break
        
    @case('dropdown')
        <select name="parameter_{{ $parameterId }}" 
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500">
            <option value="">Select ...</option>
            @if ($parameter->ecotoxConfig && $parameter->ecotoxConfig->inputValues && $parameter->ecotoxConfig->inputValues->count() > 0)
                @foreach ($parameter->ecotoxConfig->inputValues as $inputValue)
                    <option value="{{ $inputValue->input_value }}">{{ $inputValue->input_value }}</option>
                @endforeach
            @else
                {{-- no fallback options --}}
            @endif
        </select>
        @break
        
    @case('date')
        <input type="date" 
               name="parameter_{{ $parameterId }}" 
               class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500">
        @break
        
    @case('')
        <!-- No input type specified - show as read-only or disabled -->
        <span class="text-gray-400 text-xs">No input required</span>
        @break
        
    @default
        <input type="text" 
               name="parameter_{{ $parameterId }}" 
               class="w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
               placeholder="Enter {{ $columnName }}">
@endswitch
