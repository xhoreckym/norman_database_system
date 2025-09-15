<!-- Empodat Record Modal Window -->
<div x-show="showModal" 
     x-cloak 
     @keydown.escape.window="closeModal()"
     class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">

    <div class="bg-white w-11/12 md:w-2/3 lg:w-1/2 xl:w-1/3 rounded shadow-lg relative" 
         x-transition
         x-trap.inert="showModal">

        <!-- Modal Header -->
        <div class="flex justify-between items-center border-b px-4 py-2 bg-stone-600 text-white">
            <div class="flex items-center space-x-4">
                <h3 class="text-lg font-semibold">Record ID: <span x-text="recordId"></span></h3>
                <h3 class="text-lg font-semibold text-stone-200">DCT Analysis ID: <span x-text="record?.dct_analysis_id || 'N/A'"></span></h3>
            </div>
            <button @click="closeModal()" class="text-white hover:text-gray-200 text-xl">
                &times;
            </button>
        </div>

        <!-- Modal Content -->
        <div class="p-4 max-h-[60vh] overflow-y-auto">

            <div x-show="!record && showModal" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-stone-600 mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading record data...</p>
            </div>

            <!-- Record Content -->
            <div x-show="record" x-transition>
                
                <!-- Substance Section -->
                <div class="">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Substance</div>
                    <div class="flex justify-between py-1 text-sm bg-slate-100">
                        <div class="px-1 font-semibold">Substance</div>
                        <div class="px-1" x-text="record?.substance?.name || 'N/A'"></div>
                    </div>
                    <div class="flex justify-between py-1 text-sm bg-slate-200">
                        <div class="px-1 font-semibold">Code</div>
                        <!-- Dynamic link -->
                        <a :href="record?.substance_id ? `{{ route('substances.show', ':id') }}`.replace(':id', record.substance_id) : '#'"
                           target="_blank" class="link-lime-text px-1"
                           x-text="record?.substance?.prefixed_code || 'N/A'"></a>
                    </div>
                    <div class="flex justify-between py-1 text-sm bg-slate-100">
                        <div class="px-1 font-semibold">StdInChIKey</div>
                        <div class="px-1" x-text="record?.substance?.stdinchikey || 'N/A'"></div>
                    </div>
                    <div class="flex justify-between py-1 text-sm bg-slate-200">
                        <div class="px-1 font-semibold">CAS Number</div>
                        <div class="px-1" x-text="record?.substance?.cas_number || 'N/A'"></div>
                    </div>
                </div>

                <!-- Concentration Section -->
                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Concentration</div>
                <div class="flex justify-between py-1 text-sm bg-slate-100">
                    <div class="px-1 font-semibold">Concentration</div>
                    <div class="px-1">
                        <template x-if="record?.concentration_indicator_id == 0">
                            <span x-text="record?.concentration_indicator_id || 'N/A'"></span>
                        </template>
                        <template x-if="record?.concentration_indicator_id > 1">
                            <span x-text="record?.concentration_indicator?.name || 'N/A'"></span>
                        </template>
                        <template x-if="record?.concentration_indicator_id == 1">
                            <span><span class="font-medium" x-text="record?.concentration_value || 'N/A'"></span>&nbsp;<span x-text="record?.matrix?.unit || ''"></span></span>
                        </template>
                    </div>
                </div>
                <div class="flex justify-between py-1 text-sm bg-slate-200">
                    <div class="px-1 font-semibold">Sampling Date</div>
                    <div class="px-1" x-text="record?.formatted_sampling_date || 'N/A'"></div>
                </div>

                <!-- Analytical Method Section -->
                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Analytical Method</div>
                <div class="flex justify-between py-1 text-sm bg-slate-100">
                    <div class="px-1 font-semibold">sample_preparation_method_other</div>
                    <div class="px-1">
                        <template x-if="!record?.analytical_method?.sample_preparation_method_other || record?.analytical_method?.sample_preparation_method_other === null">
                            <span>N/A</span>
                        </template>
                        <template x-if="record?.analytical_method?.sample_preparation_method_other && record?.analytical_method?.sample_preparation_method_other > 0">
                            <span x-text="record?.analytical_method?.samplePreparationMethodOther?.name || 'N/A'"></span>
                        </template>
                    </div>
                </div>
                <!-- Loop over analyticalMethodArray -->
                <template x-for="(pair, index) in analyticalMethodArray" :key="index">
                    <div :class="index % 2 === 0 ? 'py-1 bg-slate-200' : 'py-1 bg-slate-100'">
                        <div class="flex justify-between py-1 text-sm">
                            <div class="px-1 font-semibold" x-text="pair[0]"></div>
                            <div class="px-1" x-text="pair[1]"></div>
                        </div>
                    </div>
                </template>

                <!-- Station Section -->
                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Station</div>
                <template x-for="(pair, index) in stationArray" :key="index">
                    <div :class="index % 2 === 0 ? 'py-1 bg-slate-100' : 'py-1 bg-slate-200'">
                        <div class="flex justify-between py-1 text-sm">
                            <div class="px-1 font-semibold" x-text="pair[0]"></div>
                            <div class="px-1" x-text="pair[1]"></div>
                        </div>
                    </div>
                </template>

                <!-- Leaflet map container -->
                <div id="map" class="mt-4 w-full h-64 bg-gray-200"></div>

                <!-- Data Source Section -->
                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Data Source</div>
                <template x-for="(pair, index) in dataSourceArray" :key="index">
                    <div :class="index % 2 === 0 ? 'py-1 bg-slate-100' : 'py-1 bg-slate-200'">
                        <div class="flex justify-between py-1 text-sm">
                            <div class="px-1 font-semibold" x-text="pair[0]"></div>
                            <div class="px-1" x-text="pair[1]"></div>
                        </div>
                    </div>
                </template>

                <!-- Matrix Data Section -->
                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Matrix Data</div>

                <template x-if="record?.matrix_data">
                    <div>
                        <div class="py-1 bg-slate-100">
                            <div class="flex justify-between py-1 text-sm">
                                <div class="px-1 font-semibold">Matrix Type</div>
                                <div class="px-1" x-text="record.matrix_data.type || 'N/A'"></div>
                            </div>
                        </div>
                        <div class="py-1 bg-slate-200">
                            <div class="flex justify-between py-1 text-sm">
                                <div class="px-1 font-semibold">Matrix Code AAA</div>
                                <div class="px-1" x-text="record.matrix_data.code || 'N/A'"></div>
                            </div>
                        </div>
                        
                        <!-- Matrix Data Details -->
                        <div class="py-1 bg-slate-100">
                            <div class="px-1">
                                <div class="font-semibold mb-2">Matrix Data Details</div>
                                
                                <!-- Basic Matrix Info -->
                                <div class="mb-3">
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div class="bg-gray-50 p-2 rounded">
                                            <span class="font-medium">Type:</span>
                                            <span class="ml-2 text-gray-600" x-text="record?.matrix_data?.type || 'N/A'"></span>
                                        </div>
                                        <div class="bg-gray-50 p-2 rounded">
                                            <span class="font-medium">Code:</span>
                                            <span class="ml-2 text-gray-600" x-text="record?.matrix_data?.code || 'N/A'"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Meta Data Table -->
                                <div class="mb-3">
                                    <div class="font-medium mb-2">Meta Data:</div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs border border-gray-300">
                                            <thead>
                                                <tr class="bg-gray-200">
                                                    <th class="border border-gray-300 px-2 py-1 text-left font-semibold">Field</th>
                                                    <th class="border border-gray-300 px-2 py-1 text-left font-semibold">Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="(value, key) in record?.matrix_data?.meta_data || {}" :key="key">
                                                    <tr class="bg-white">
                                                        <td class="border border-gray-300 px-2 py-1 font-medium text-gray-700" x-text="key"></td>
                                                        <td class="border border-gray-300 px-2 py-1 text-gray-600">
                                                            <template x-if="value === null">
                                                                <span class="text-gray-400 italic">(null)</span>
                                                            </template>
                                                            <template x-if="value === ''">
                                                                <span class="text-gray-400 italic">(empty)</span>
                                                            </template>
                                                            <template x-if="value !== null && value !== ''">
                                                                <span x-text="value"></span>
                                                            </template>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!record?.matrix_data">
                    <div class="py-1 bg-slate-100">
                        <div class="flex justify-between py-1 text-sm">
                            <div class="px-1 font-semibold">Matrix Data</div>
                            <div class="px-1 text-gray-500">No matrix data available</div>
                        </div>
                    </div>
                </template>

            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end border-t px-4 py-2">
            <button @click="closeModal()" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Close
            </button>
        </div>
    </div>
</div>
