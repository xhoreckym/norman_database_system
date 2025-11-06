@if (str_starts_with($moduleKey, 'arbg.'))
<span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded">ARBG</span>
@elseif(str_starts_with($moduleKey, 'ecotox'))
<span class="px-2 py-0.5 bg-green-100 text-green-800 rounded">Ecotox</span>
@elseif(str_starts_with($moduleKey, 'empodat'))
<span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded">EMPODAT</span>
@elseif(str_starts_with($moduleKey, 'passive'))
<span class="px-2 py-0.5 bg-teal-100 text-teal-800 rounded">Passive</span>
@elseif(str_starts_with($moduleKey, 'indoor'))
<span class="px-2 py-0.5 bg-orange-100 text-orange-800 rounded">Indoor</span>
@elseif(str_starts_with($moduleKey, 'bioassay'))
<span class="px-2 py-0.5 bg-pink-100 text-pink-800 rounded">Bioassay</span>
@elseif(str_starts_with($moduleKey, 'sars'))
<span class="px-2 py-0.5 bg-red-100 text-red-800 rounded">SARS</span>
@else
<span class="px-2 py-0.5 bg-gray-100 text-gray-800 rounded">Other</span>
@endif