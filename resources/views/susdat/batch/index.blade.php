<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        
        <div class="p-6 text-gray-900">
          
          <!-- Page Header -->
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Batch Conversion of Identifiers</h1>
            <p class="text-gray-600">Convert multiple identifiers to SUSDAT substance information</p>
          </div>
          
          <!-- Conversion Form -->
          <form action="{{ route('susdat.batch.convert') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Input Type Selection -->
            <div>
              <label for="input_type" class="block text-sm font-medium text-gray-700 mb-2">
                Input Data Type
              </label>
              <select name="input_type" id="input_type" required 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-lime-500 focus:border-lime-500">
                <option value="">Select input type...</option>
                <option value="cas_no" {{ isset($formData['input_type']) && $formData['input_type'] == 'cas_no' ? 'selected' : '' }}>CAS Number</option>
                <option value="substance_name" {{ isset($formData['input_type']) && $formData['input_type'] == 'substance_name' ? 'selected' : '' }}>Substance Name</option>
                <option value="std_inchikey" {{ isset($formData['input_type']) && $formData['input_type'] == 'std_inchikey' ? 'selected' : '' }}>StdInChIKey</option>
              </select>
              @error('input_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>
            
            <!-- Identifiers Input -->
            <div>
              <label for="identifiers" class="block text-sm font-medium text-gray-700 mb-2">
                Identifiers (one per line)
              </label>
              <textarea name="identifiers" id="identifiers" rows="10" required
                        placeholder="Enter identifiers here, one per line&#10;Example:&#10;67-56-1&#10;108-88-3&#10;71-43-2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-lime-500 focus:border-lime-500 font-mono text-sm">{{ $formData['identifiers'] ?? '' }}</textarea>
              @error('identifiers')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
              <p class="mt-1 text-sm text-gray-500">
                Enter each identifier on a separate line. Maximum 10,000 characters allowed.
              </p>
            </div>
            
            <!-- Submit Button -->
            <div class="flex justify-end">
              <button type="submit" class="btn-submit px-6 py-3">
                {{ isset($formData) ? 'Update Conversion' : 'Convert Identifiers' }}
              </button>
            </div>
          </form>
          
          <!-- Instructions -->
          <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 mb-3">How to use:</h3>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-start">
                <span class="text-lime-600 mr-2">1.</span>
                <span>Select the type of input data you're providing (CAS Number, Substance Name, or StdInChIKey)</span>
              </li>
              <li class="flex items-start">
                <span class="text-lime-600 mr-2">2.</span>
                <span>Enter your identifiers in the text area, one per line</span>
              </li>
              <li class="flex items-start">
                <span class="text-lime-600 mr-2">3.</span>
                <span>Click "Convert Identifiers" to process your list</span>
              </li>
              <li class="flex items-start">
                <span class="text-lime-600 mr-2">4.</span>
                <span>View the results table with SUSDAT ID, substance name, CAS number, and StdInChIKey</span>
              </li>
            </ul>
          </div>
          
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
