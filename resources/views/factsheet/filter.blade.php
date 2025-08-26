<x-app-layout>
  <x-slot name="header">
    @include('factsheet.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        
        <!-- Main Search form -->
        <form x-data="{ 
          loading: false, 
          seconds: 0,
          timerInterval: null,
          controller: null,
          startTimer() {
              this.seconds = 0;
              this.timerInterval = setInterval(() => {
                  this.seconds++;
              }, 1000);
              this.controller = new AbortController();
          },
          stopTimer() {
              clearInterval(this.timerInterval);
              this.seconds = 0;
          },
          cancelRequest() {
              if (this.controller) {
                  this.controller.abort();
              }
              this.stopTimer();
              this.loading = false;
              alert('Search request has been cancelled.');
          },
          formatTime(totalSeconds) {
              const minutes = Math.floor(totalSeconds / 60);
              const seconds = totalSeconds % 60;
              return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
          },
          submitForm(event) {
              event.preventDefault();
              
              this.loading = true;
              this.startTimer();
              
              const form = event.target;
              const formData = new FormData(form);
              const queryString = new URLSearchParams(formData).toString();
              const url = form.action + '?' + queryString;
              
              fetch(url, {
                  method: 'GET',
                  signal: this.controller.signal
              })
              .then(response => {
                  if (response.ok) {
                      window.location.href = url;
                  } else {
                      throw new Error('Search failed');
                  }
              })
              .catch(error => {
                  if (error.name === 'AbortError') {
                      console.log('Search was cancelled');
                  } else {
                      console.error('Error:', error);
                      alert('There was an error processing your search. Please try again.');
                  }
                  this.loading = false;
                  this.stopTimer();
              });
          }
      }" 
        @submit="submitForm($event)"
        name="searchFactsheet" 
        id="searchFactsheet" 
        action="{{route('factsheets.search.search')}}" 
        method="GET">
        
        <!-- Full-screen overlay with timer and cancel button -->
        <div x-show="loading" 
        class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-gray-900 bg-opacity-80"
        style="display: none;">
        <div class="text-center p-8 bg-white rounded-lg shadow-xl max-w-md w-full">
          <h2 class="text-xl font-bold text-slate-700 mb-2">Factsheet Search</h2>
          
          <svg class="mx-auto animate-spin h-20 w-20 text-slate-500 my-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          
          <div class="text-lg font-semibold text-slate-700 mb-2">
            Searching for factsheet data...
          </div>
          
          <div class="text-2xl font-mono text-slate-600 mb-4" x-text="formatTime(seconds)"></div>
          
          <button 
            @click="cancelRequest()"
            type="button"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 ease-in-out">
            Cancel Search
          </button>
        </div>
      </div>
        
        <div class="p-6">
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Substance Factsheets Search</h2>
            <p class="text-gray-600">Search for substances across all NORMAN Database System modules to view comprehensive factsheet information.</p>
          </div>

          <!-- Substance Search Component -->
          <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Substance Selection</h3>
            @livewire('factsheet.substance-search', ['existingSubstances' => $substances])
          </div>

          <!-- Search Button -->
          <div class="flex justify-center">
            <button 
              type="submit" 
              class="btn-submit px-8 py-3 text-lg"
              x-data="{ formSubmitted: false }"
              @click="formSubmitted = true"
              x-bind:disabled="formSubmitted">
              <span x-show="!formSubmitted">Search Factsheets</span>
              <span x-show="formSubmitted" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Searching...
              </span>
            </button>
          </div>

          <!-- Information Box -->
          <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
            <h4 class="font-semibold text-slate-800 mb-2">What you'll find:</h4>
            <ul class="text-sm text-slate-600 space-y-1">
              <li>• <strong>Chemical Occurrence Data:</strong> Environmental monitoring data across Europe</li>
              <li>• <strong>Ecotoxicology:</strong> Toxicity studies and environmental quality standards</li>
              <li>• <strong>Indoor Environment:</strong> Data from indoor air and dust samples</li>
              <li>• <strong>Passive Sampling:</strong> Data obtained with passive samplers</li>
              <li>• <strong>Bioassays:</strong> Biological effect data from environmental samples</li>
            </ul>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
</x-app-layout>