<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">

        <form name="searchLiterature" id="searchLiterature" action="{{route('literature.search.search')}}" method="GET">

          <div class="p-4 text-gray-900 grid grid-cols-1 gap-4">

            <div id="searchFiles">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2 flex items-center space-x-2">
                  <i class="fas fa-file text-gray-600" aria-hidden="true"></i>
                  <span>File criteria:</span>
                </div>
                <div aria-describedby="file-help">
                  @livewire('literature.file-search', ['existingFiles' => request('fileSearch', [])])
                </div>
                <div id="file-help" class="sr-only">Search and select files to filter records that are associated with specific files</div>

                <!-- File search instructions -->
                <div class="mt-2 text-xs text-gray-600">
                  <p><i class="fas fa-info-circle mr-1"></i> Search by file name or select from the dropdown. Only records associated with the selected files will be shown.</p>
                </div>
              </div>
            </div>

            <div id="displayOptions">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Display options:
                </div>
                <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                  <label class="inline-flex items-center">
                    <input type="radio"
                           class="form-radio text-slate-600"
                           name="displayOption"
                           value="1"
                           @if (request('displayOption', '1') == 1) checked @endif>
                    <span class="ml-2">Fast data preview</span>
                  </label>
                  <label class="inline-flex items-start sm:items-center">
                    <input type="radio"
                           class="form-radio text-slate-600 mt-1 sm:mt-0"
                           name="displayOption"
                           value="0"
                           @if (request('displayOption', '1') === '0') checked @endif>
                    <span class="ml-2">Data output with page links (might be slow)</span>
                  </label>
                </div>
              </div>
            </div>

            <div id="searchGeography">
              <div class="bg-gray-100 p-2">
                <div class="flex flex-col lg:flex-row">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Country criteria:
                    </div>
                    <div>
                      @include('_t.form-apline-multiselect', [
                        'tag' => 'countrySearch', 'list' => $countryList,
                        'active_ids' => isset($request->countrySearch) ? $request->countrySearch : [],
                      ])
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div id="searchSpecies">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Species criteria:
                </div>
                <div>
                  @include('_t.form-apline-multiselect', [
                    'tag' => 'speciesSearch', 'list' => $speciesList,
                    'active_ids' => isset($request->speciesSearch) ? $request->speciesSearch : [],
                  ])
                </div>
              </div>
            </div>

            <div id="searchClass">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Class criteria:
                </div>
                <div>
                  @include('_t.form-apline-multiselect', [
                    'tag' => 'classSearch', 'list' => $classList,
                    'active_ids' => isset($request->classSearch) ? $request->classSearch : [],
                  ])
                </div>
              </div>
            </div>

            <div id="searchTissue">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Tissue criteria:
                </div>
                <div>
                  @include('_t.form-apline-multiselect', [
                    'tag' => 'tissueSearch', 'list' => $tissueList,
                    'active_ids' => isset($request->tissueSearch) ? $request->tissueSearch : [],
                  ])
                </div>
              </div>
            </div>

            <div id="searchTypeOfNumericQuantity">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Type of numeric quantity criteria:
                </div>
                <div>
                  @include('_t.form-apline-multiselect', [
                    'tag' => 'typeOfNumericQuantitySearch', 'list' => $typeOfNumericQuantityList,
                    'active_ids' => isset($request->typeOfNumericQuantitySearch) ? $request->typeOfNumericQuantitySearch : [],
                  ])
                </div>
              </div>
            </div>

            <div id="searchSubstance">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Substance criteria:
                </div>
                <div>
                  @livewire('empodat.substance-search', ['existingSubstances' => $request->substances])
                </div>
              </div>
            </div>

            <div id="searchCategory" class="">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Search Category:
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
                  @foreach ($categories as $category)
                  <div class="block p-1">
                    <span>
                      <input type="checkbox"
                             name="categoriesSearch[]"
                             value="{{$category->id}}"
                             @if (is_array(request('categoriesSearch')) && in_array($category->id, request('categoriesSearch'))) checked @endif>
                    </span>
                    <span class="ml-1">
                      {!! str_replace(' (', '&nbsp;(', $category->name_abbreviation) !!}
                    </span>
                  </div>
                  @endforeach
                </div>
              </div>
            </div>

            <!-- Main Search form -->
            <div class="flex justify-end m-2">
              <a href="{{route('literature.search.filter')}}" class="btn-clear mx-2"> Reset </a>
              <button type="submit" class="btn-submit"> Search
              </button>
            </div>

            <div class="m-2">
              <ul class="list-disc list-inside text-gray-700 text-sm">
                <li>All search criteria are optional. If you do not select any criteria, all data will be displayed.</li>
                <li>Each time the search is executed, the search options are recorded in the database for future reference.</li>
              </ul>
            </div>

          </div>

        </form>
      </div>
    </div>
  </div>
</x-app-layout>

