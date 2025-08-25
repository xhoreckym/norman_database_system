<x-app-layout>
  <div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb Navigation -->
    <nav class="mb-6">
      <ol class="flex items-center space-x-2 text-sm text-gray-500">
        <li>
          <a href="{{ route('ecotox.credevaluation.home.index') }}" class="link-lime-text hover:text-lime-700">
            CRED Evaluation
          </a>
        </li>
        <li>
          <span class="mx-2">/</span>
        </li>
        <li class="text-gray-800 font-medium">
          @if ($recordId)
            Evaluation Form for Record {{ $recordId }}
          @else
            Demo Evaluation Form
          @endif
        </li>
      </ol>
    </nav>


    <div>

      <div class="grid grid-cols-3 gap-4">
        <div class="col-span-1">
          @if ($returnUrl)
            <div class="mb-4">
              <a href="{{ $returnUrl }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                ← Go Back to Search Results
              </a>
            </div>
          @endif
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              @if ($recordId)
                CRED Evaluation Form
              @else
                CRED Evaluation Demo Form
              @endif
            </h1>
            @if ($recordId)
              <p class="text-gray-600">Evaluating Record ID: {{ $recordId }}</p>
            @else
              <p class="text-gray-600">This is a demonstration of the CRED evaluation form. You can test the scoring
                system and see how questions are organized.</p>
            @endif
          </div>
        </div>
        <div class="col-span-2">
        @if ($record)
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Record Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">ECOTOX ID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->ecotox_id }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Substance</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->substance->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Taxonomic Group</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->taxonomic_group ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Scientific Name</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->scientific_name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Endpoint</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->endpoint ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Test Type</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $record->test_type ?? 'N/A' }}</p>
              </div>
            </div>
          </div>
    
          <!-- Substances Context -->
          @if ($substances && $substances->count() > 0)
            <div class="mb-6 bg-slate-50 border border-slate-200 rounded-lg p-4">
              <h3 class="text-sm font-medium text-slate-700 mb-2">Search Context</h3>
              <p class="text-sm text-slate-600 mb-2">You are evaluating this record as part of a search for the following
                substances:</p>
              <div class="flex flex-wrap gap-2">
                @foreach ($substances as $substance)
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                    {{ $substance->name }}
                  </span>
                @endforeach
              </div>
            </div>
          @endif
        @endif
        </div>
      </div>
    </div>

    <!-- Top Go Back Button -->




    <!-- Record Information Section -->


    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">CRED Evaluation Questions</h2>
        <p class="text-sm text-gray-600 mt-1">Rate each question based on the quality and reliability of the data</p>
      </div>

      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Question
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question Text
            </th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Max Score
            </th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Screening
              Score</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Your Score
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          @if ($credQuestions && $credQuestions->count() > 0)
            @foreach ($credQuestions as $question)
              <!-- Main Question -->
              <tr class="bg-gray-50 hover:bg-gray-100">
                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                  {{ $question->question_number }}.
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                  {{ $question->question_text }}
                </td>
                <td class="px-6 py-4 text-sm text-center text-gray-800 font-medium">
                  {{ $question->max_score }}
                </td>
                <td class="px-6 py-4 text-sm text-center text-gray-800 font-medium">
                  {{ $question->screening_score ?? '-' }}
                </td>
                <td class="px-6 py-4 text-center">
                  <input type="number" name="score_{{ $question->id }}" min="0"
                    max="{{ $question->max_score }}" step="0.1"
                    class="score-input w-16 px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                    placeholder="0" data-max-score="{{ $question->max_score }}">
                </td>
              </tr>

              <!-- Sub Questions -->
              @if ($question->subQuestions && $question->subQuestions->count() > 0)
                @foreach ($question->subQuestions as $subQuestion)
                  <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-sm text-gray-600 pl-12">
                      {{ $subQuestion->question_letter }}
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-600">
                      {{ $subQuestion->question_text }}
                    </td>
                    <td class="px-6 py-3 text-sm text-center text-gray-700">
                      {{ $subQuestion->max_score }}
                    </td>
                    <td class="px-6 py-3 text-sm text-center text-gray-500">
                      -
                    </td>
                    <td class="px-6 py-3 text-center">
                      <input type="number" name="score_{{ $subQuestion->id }}" min="0"
                        max="{{ $subQuestion->max_score }}" step="0.1"
                        class="score-input w-16 px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                        placeholder="0" data-max-score="{{ $subQuestion->max_score }}">
                    </td>
                  </tr>
                @endforeach
              @endif
            @endforeach
          @else
            <tr>
              <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">
                No CRED questions available
              </td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>

    <!-- Summary Section -->
    {{-- @if ($credQuestions && $credQuestions->count() > 0)
      <div class="mt-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Evaluation Summary</h2>
        
        <!-- Progress Bar -->
        <div class="mb-6">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">Evaluation Progress</span>
            <span class="text-sm text-gray-500" id="progress-text">0%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-slate-600 h-2 rounded-full transition-all duration-300" id="progress-bar" style="width: 0%"></div>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Total Questions</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $credQuestions->count() }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Total Sub-questions</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $credQuestions->sum(function($q) { return $q->subQuestions->count(); }) }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Maximum Possible Score</h3>
            <p class="text-2xl font-bold text-gray-900" id="max-possible-score">{{ $credQuestions->sum('max_score') + $credQuestions->sum(function($q) { return $q->subQuestions->sum('max_score'); }) }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Your Total Score</h3>
            <p class="text-2xl font-bold text-slate-600" id="your-total-score">0</p>
          </div>
        </div>
        
        <!-- Score Quality Indicator -->
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
          <h3 class="text-sm font-medium text-gray-800 mb-2">Score Quality Assessment</h3>
          <div class="flex items-center space-x-2">
            <div class="w-4 h-4 rounded-full" id="quality-indicator"></div>
            <span class="text-sm text-gray-600" id="quality-text">Enter scores to see quality assessment</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-6 flex justify-between items-center">
        <!-- Left side - Go Back button -->
        @if ($returnUrl)
          <a href="{{ $returnUrl }}" class="px-6 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
            ← Go Back to Search Results
          </a>
        @else
          <a href="{{ route('ecotox.credevaluation.home.index') }}" class="px-6 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
            ← Back to Home
          </a>
        @endif
        
        <!-- Right side - Action buttons -->
        <div class="flex space-x-4">
          @if ($recordId)
            <button type="button" class="btn-submit px-6 py-2 text-sm font-medium text-white bg-slate-600 border border-transparent rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
              Save Evaluation
            </button>
          @else
            <button type="button" class="btn-submit px-6 py-2 text-sm font-medium text-white bg-slate-600 border border-transparent rounded-md hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
              Export Scores
            </button>
          @endif
        </div>
      </div>
    @endif --}}
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const scoreInputs = document.querySelectorAll('.score-input');
        const yourTotalScore = document.getElementById('your-total-score');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const qualityIndicator = document.getElementById('quality-indicator');
        const qualityText = document.getElementById('quality-text');

        function calculateTotalScore() {
          let total = 0;
          scoreInputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            total += value;
          });
          yourTotalScore.textContent = total.toFixed(1);

          // Update color based on score
          const maxPossible = parseFloat(document.getElementById('max-possible-score').textContent);
          const percentage = (total / maxPossible) * 100;

          if (percentage >= 80) {
            yourTotalScore.className = 'text-2xl font-bold text-green-600';
            qualityIndicator.className = 'bg-green-500';
            qualityText.textContent = 'Excellent';
          } else if (percentage >= 60) {
            yourTotalScore.className = 'text-2xl font-bold text-yellow-600';
            qualityIndicator.className = 'bg-yellow-500';
            qualityText.textContent = 'Good';
          } else {
            yourTotalScore.className = 'text-2xl font-bold text-red-600';
            qualityIndicator.className = 'bg-red-500';
            qualityText.textContent = 'Poor';
          }

          // Update progress bar
          progressBar.style.width = `${percentage}%`;
          progressText.textContent = `${percentage.toFixed(0)}%`;
        }

        // Add event listeners to all score inputs
        scoreInputs.forEach(input => {
          input.addEventListener('input', calculateTotalScore);
          input.addEventListener('change', calculateTotalScore);
        });

        // Initial calculation
        calculateTotalScore();
      });
    </script>
</x-app-layout>
