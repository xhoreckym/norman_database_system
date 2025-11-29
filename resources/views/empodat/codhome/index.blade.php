<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN EMPODAT Database - Chemical Occurrence Data
          </h1>
          
          <!-- Description -->
          <p class="text-gray-700 leading-relaxed mb-4">
            EMPODAT is a database of geo-referenced monitoring and bio-monitoring data on emerging substances in the following matrices: water, sediments, biota, SPM, soil, sewage sludge, and air.
          </p>
          
          <!-- Subheading -->
          <h2 class="text-lg font-bold text-gray-800 mb-2">The EMPODAT Database consists of:</h2>
          
          <!-- Bulleted List -->
          <ul class="list-disc list-inside text-gray-700 mb-4">
            <li>
              The EMPODAT Database comprises monitoring/occurrence data on emerging substances, with focus on those which are already known to be present in the environment but which are not yet included in routine monitoring programmes.
            </li>
          </ul>
          
          <!-- Additional Information -->
          <p class="text-gray-700 leading-relaxed mb-4">
            EMPODAT is designed to allow:
          </p>
          <ul class="list-disc list-inside text-gray-700 mb-4">
            <li>
              Access to the latest information on emerging pollutants, with an overview of benchmark values on the occurrence of emerging substances across Europe;
            </li>
            <li>
              Identification of gaps in data relating to time, geographical areas, and/or environmental matrices.
            </li>
          </ul>
          
          <!-- Submitting Data Section -->
          <h2 class="text-xl font-bold text-gray-800 mb-2">How to submit data</h2>
          
          <hr class="border-t-2 border-lime-500 mb-4">
          
          <!-- Instructions -->
          <p class="text-gray-700 leading-relaxed mb-4">
            For information and conditions for the inclusion of your data in the NORMAN Database System, see NORMAN Database System Data Policy.
          </p>
          <p class="text-gray-700 leading-relaxed mb-4">
            To include data into the NORMAN Database, DATA COLLECTION TEMPLATES (DCT) in Excel were developed for each matrix. These DCTs can be downloaded at 
            <a href="{{ route('empodat.templates', ['code' => 'empodat']) }}" class="link-lime-text">
              Download DCT Templates
            </a>.
          </p>
          
          <!-- Contact Information -->
          <p class="text-gray-700 leading-relaxed mb-4">
            The completed DCTs should be sent to the NORMAN Database development team: 
            <a href="mailto:norman@ei.sk" class="link-lime-text">norman@ei.sk</a> with a copy to 
            <a href="mailto:oswaldova@ei.sk" class="link-lime-text">oswaldova@ei.sk</a>, for further processing and upload to the web-database.
          </p>
          <p class="text-gray-700 leading-relaxed">
            Large datasets, available in other than Excel format, can be uploaded as well, after communication with the NORMAN Database Development team – to check the structure of data, availability of obligatory information, and to agree on the optimal way of the data transfer.
          </p>
        </div>
        
        
      </div>
    </div>
  </div>
  
</x-app-layout>