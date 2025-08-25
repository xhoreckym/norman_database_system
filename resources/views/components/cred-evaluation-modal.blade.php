@props(['authCheck' => false, 'isSuperAdmin' => false])

<!-- CRED Evaluation Modal Window -->
<div x-show="showCredEvaluationModal" 
     x-cloak 
     @keydown.escape.window="closeModalCredEvaluation()"
     class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">

    <div class="bg-white w-11/12 md:w-4/5 lg:w-4/5 xl:w-3/4 rounded shadow-lg relative" 
         x-transition>

        <!-- Modal Header -->
        <div class="flex justify-between items-center border-b px-4 py-2 bg-sky-600 text-white">
            <div class="flex items-center space-x-4">
                <h3 class="text-lg font-semibold">
                    CRED Evaluation for Record ID: <span x-text="credEvaluationRecordId"></span>
                </h3>
                <h3 class="text-lg font-semibold text-sky-200">
                    Biotest ID: <span x-text="credEvaluationRecord?.ecotox_id || 'N/A'"></span>
                </h3>
            </div>
            <button @click="closeModalCredEvaluation()" class="text-white hover:text-gray-200 text-xl">
                &times;
            </button>
        </div>

        <!-- Modal Content -->
        <div class="p-4 max-h-[70vh] overflow-y-auto">
            <!-- Loading State -->
            <div x-show="!credEvaluationRecord && showCredEvaluationModal" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-sky-600 mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading CRED evaluation data...</p>
            </div>

            <!-- CRED Evaluation Content -->
            <div x-show="credEvaluationRecord" x-transition>
                <div class="space-y-6">
                    <!-- Record Information Summary -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-3">Record Information</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-600">Taxonomic Group:</span>
                                <span x-text="credEvaluationRecord?.taxonomic_group || 'N/A'" class="ml-2"></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Scientific Name:</span>
                                <span x-text="credEvaluationRecord?.scientific_name || 'N/A'" class="ml-2"></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Endpoint:</span>
                                <span x-text="credEvaluationRecord?.endpoint || 'N/A'" class="ml-2"></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Matrix:</span>
                                <span x-text="credEvaluationRecord?.matrix_habitat || 'N/A'" class="ml-2"></span>
                            </div>
                        </div>
                    </div>

                    <!-- CRED Evaluation Form -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-4">CRED Evaluation Criteria</h4>
                        
                        <!-- Evaluation Sections -->
                        <div class="space-y-4">
                            <!-- Reliability Score -->
                            <div class="border-b border-gray-100 pb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Reliability Score
                                </label>
                                <select x-model="credEvaluationData.reliabilityScore" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                    <option value="">Select reliability score</option>
                                    <option value="1">1 - Reliable without restrictions</option>
                                    <option value="2">2 - Reliable with restrictions</option>
                                    <option value="3">3 - Not reliable</option>
                                    <option value="4">4 - Not assignable</option>
                                </select>
                            </div>

                            <!-- Use of Study -->
                            <div class="border-b border-gray-100 pb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Use of Study
                                </label>
                                <select x-model="credEvaluationData.useOfStudy" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                    <option value="">Select use of study</option>
                                    <option value="key">Key study</option>
                                    <option value="supporting">Supporting study</option>
                                    <option value="not_used">Not used</option>
                                </select>
                            </div>

                            <!-- Additional Comments -->
                            <div class="border-b border-gray-100 pb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Additional Comments
                                </label>
                                <textarea 
                                    x-model="credEvaluationData.comments" 
                                    rows="3" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
                                    placeholder="Enter any additional comments or justification for the evaluation..."></textarea>
                            </div>

                            <!-- Evaluation Date -->
                            <div class="border-b border-gray-100 pb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Evaluation Date
                                </label>
                                <input 
                                    type="date" 
                                    x-model="credEvaluationData.evaluationDate" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                        </div>
                    </div>

                    <!-- Previous Evaluations (if any) -->
                    <div x-show="credEvaluationHistory && credEvaluationHistory.length > 0" class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-3">Previous Evaluations</h4>
                        <div class="space-y-2">
                            <template x-for="evaluation in credEvaluationHistory" :key="evaluation.id">
                                <div class="bg-white p-3 rounded border border-gray-200">
                                    <div class="flex justify-between items-start">
                                        <div class="text-sm">
                                            <span class="font-medium">Reliability:</span>
                                            <span x-text="evaluation.reliability_score" class="ml-2"></span>
                                            <span class="font-medium ml-4">Use:</span>
                                            <span x-text="evaluation.use_of_study" class="ml-2"></span>
                                        </div>
                                        <div class="text-xs text-gray-500" x-text="evaluation.evaluated_at"></div>
                                    </div>
                                    <div x-show="evaluation.comments" class="text-sm text-gray-600 mt-2" x-text="evaluation.comments"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-between border-t px-4 py-2">
            <button @click="saveCredEvaluation()" 
                    class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                Save Evaluation
            </button>
            <button @click="closeModalCredEvaluation()" 
                    class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Close
            </button>
        </div>
    </div>
</div>
