<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <h1 class="text-2xl font-bold text-gray-900 mb-4">Ecotox CRED Evaluation</h1>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
              <h2 class="text-lg font-semibold text-blue-800 mb-3">CRED Evaluation Search</h2>
              <p class="text-blue-700 mb-4">
                Search for ecotoxicology data with CRED evaluation criteria. This tool allows you to find and analyze data based on CRED assessment metrics.
              </p>
              <a href="{{ route('ecotox.credevaluation.search.filter') }}" class="btn-submit">
                Start CRED Evaluation
              </a>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
              <h2 class="text-lg font-semibold text-green-800 mb-3">About CRED Evaluation</h2>
              <p class="text-green-700 mb-4">
                CRED evaluation helps assess the reliability and relevance of ecotoxicology data for regulatory and research purposes.
              </p>
              <div class="text-sm text-green-600">
                <p>• Data reliability scoring</p>
                <p>• Study quality evaluation</p>
                <p>• Regulatory compliance assessment</p>
              </div>
            </div>
          </div>
          
          <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Getting Started</h2>
            <div class="text-gray-700 space-y-2">
              <p>1. <strong>Select Substances:</strong> Search and select the substances you want to analyze</p>
              <p>2. <strong>Review Results:</strong> Examine the quality assessment data for each record</p>
              <p>3. <strong>Filter and Export:</strong> Use advanced filters to refine your search results</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
