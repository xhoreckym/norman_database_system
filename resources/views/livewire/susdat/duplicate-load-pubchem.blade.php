<div>
    <div  wire:init="init">
        @if ($response)
        <table class="table-standard">
            <thead>
                <tr class="bg-gray-600 text-white">
                    @foreach ($columns as $pubchemField => $normanField)
                    <th class="py-1 px-2">{{$normanField}}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $r)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                    @foreach ($columns as $pubchemField => $normanField)
                    <td class="p-1">
                        @if(is_null($r[$normanField]))
                        <span class="text-slate-600 pl-5">No data available</span>
                        @else
                        {{$r[$normanField]}}
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <span class="text-slate-600 pl-5">Loading data from Pubchem...</span>
        @endif
    </div>
</div>
