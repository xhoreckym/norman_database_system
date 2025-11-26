<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Flash Messages -->
          @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
              {{ session('success') }}
            </div>
          @endif
          @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
              {{ session('error') }}
            </div>
          @endif

          <!-- File Actions -->
          <div class="mb-6 flex justify-between items-center">
            <div>
              @if(!isset($isCreate) || !$isCreate)
                <div class="mb-2">
                  <span class="font-mono text-lg font-bold text-gray-900 bg-gray-200 px-3 py-1 rounded">ID: {{ $file->id }}</span>
                </div>
              @endif
              <h2 class="text-xl font-semibold text-gray-800">
                {{ isset($isCreate) && $isCreate ? 'Upload New File' : 'Edit File' }}
              </h2>
            </div>
            @if(!isset($isCreate) || !$isCreate)
              <a href="{{ route('files.show', $file) }}" class="link-lime-text">
                View Details
              </a>
            @endif
          </div>

          <form
            action="{{ isset($isCreate) && $isCreate ? route('files.store') : route('files.update', $file) }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6"
          >
            @csrf
            @if(!isset($isCreate) || !$isCreate)
              @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <!-- Column 1 - Basic Information -->
              <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>

                  <!-- Name -->
                  <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                    <input
                      type="text"
                      name="name"
                      id="name"
                      value="{{ old('name', $file->name) }}"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('name') border-red-500 @enderror"
                      placeholder="Enter a display name"
                    >
                    @error('name')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Description -->
                  <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea
                      name="description"
                      id="description"
                      rows="3"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('description') border-red-500 @enderror"
                      placeholder="File description..."
                    >{{ old('description', $file->description) }}</textarea>
                    @error('description')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- DOI -->
                  <div class="mb-4">
                    <label for="doi" class="block text-sm font-medium text-gray-700 mb-1">DOI</label>
                    <input
                      type="text"
                      name="doi"
                      id="doi"
                      value="{{ old('doi', $file->doi) }}"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('doi') border-red-500 @enderror"
                      placeholder="e.g., 10.60954/empodat.xxxx"
                    >
                    @error('doi')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Processing Notes -->
                  <div>
                    <label for="processing_notes" class="block text-sm font-medium text-gray-700 mb-1">Processing Notes</label>
                    <textarea
                      name="processing_notes"
                      id="processing_notes"
                      rows="3"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('processing_notes') border-red-500 @enderror"
                      placeholder="Processing notes..."
                    >{{ old('processing_notes', $file->processing_notes) }}</textarea>
                    @error('processing_notes')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- File Upload Section -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">File Upload</h3>

                  @if(isset($isCreate) && $isCreate)
                    <div>
                      <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Select File</label>
                      <input
                        type="file"
                        name="file"
                        id="file"
                        class="block w-full text-sm text-gray-500 @error('file') border-red-500 @enderror
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-gray-200 file:text-gray-800
                              hover:file:bg-gray-300"
                        required
                      >
                      <p class="mt-2 text-sm text-gray-500">Maximum file size: 20MB</p>
                      @error('file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  @else
                    <div class="mb-4">
                      <label class="block text-sm font-medium text-gray-700 mb-2">Current File</label>
                      <div class="bg-white p-3 rounded border border-gray-300">
                        <div class="flex items-center justify-between">
                          <div>
                            <div class="font-medium text-gray-900">{{ $file->original_name }}</div>
                            <div class="text-sm text-gray-500">
                              {{ $file->formatted_file_size }} • {{ $file->mime_type }}
                            </div>
                          </div>
                          @if($file->file_path && $file->existsOnDisk())
                            <a href="{{ route('files.download', $file) }}" class="link-lime-text text-sm">Download</a>
                          @else
                            <span class="text-gray-500 text-sm">Not Available</span>
                          @endif
                        </div>
                      </div>
                    </div>

                    <div>
                      <label for="new_file" class="block text-sm font-medium text-gray-700 mb-1">Replace File (Optional)</label>
                      <input
                        type="file"
                        name="new_file"
                        id="new_file"
                        class="block w-full text-sm text-gray-500 @error('new_file') border-red-500 @enderror
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-gray-200 file:text-gray-800
                              hover:file:bg-gray-300"
                      >
                      @error('new_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  @endif
                </div>

                <!-- Note -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Note</h3>
                  <textarea
                    name="note"
                    id="note"
                    rows="4"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('note') border-red-500 @enderror"
                    placeholder="Additional notes..."
                  >{{ old('note', $file->note) }}</textarea>
                  @error('note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <!-- Column 2 - Associations & Settings -->
              <div class="space-y-6">
                <!-- Project Assignment -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Associations</h3>

                  <!-- Project -->
                  <div class="mb-4">
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                    <select
                      name="project_id"
                      id="project_id"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('project_id') border-red-500 @enderror"
                    >
                      <option value="">-- No Project --</option>
                      @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ (old('project_id', $file->project_id) == $project->id) ? 'selected' : '' }}>
                          {{ $project->name }}
                          @if($project->abbreviation)
                            ({{ $project->abbreviation }})
                          @endif
                        </option>
                      @endforeach
                    </select>
                    @error('project_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Database Entity -->
                  <div class="mb-4">
                    <label for="database_entity_id" class="block text-sm font-medium text-gray-700 mb-1">Database Entity</label>
                    <select
                      name="database_entity_id"
                      id="database_entity_id"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('database_entity_id') border-red-500 @enderror"
                    >
                      <option value="">-- No Database Entity --</option>
                      @foreach($databaseEntities as $databaseEntity)
                        <option value="{{ $databaseEntity->id }}" {{ (old('database_entity_id', $file->database_entity_id) == $databaseEntity->id) ? 'selected' : '' }}>
                          {{ $databaseEntity->name }}
                        </option>
                      @endforeach
                    </select>
                    @error('database_entity_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Template -->
                  <div class="mb-4">
                    <label for="template_id" class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                    <select
                      name="template_id"
                      id="template_id"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('template_id') border-red-500 @enderror"
                    >
                      <option value="">-- No Template --</option>
                      @foreach($templates as $template)
                        <option value="{{ $template->id }}" {{ (old('template_id', $file->template_id) == $template->id) ? 'selected' : '' }}>
                          {{ $template->name }}
                          @if(isset($template->version))
                            (v{{ $template->version }})
                          @endif
                        </option>
                      @endforeach
                    </select>
                    @error('template_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Uploaded By -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Uploaded By</label>
                    @livewire('backend.user-search', ['selectedUserId' => old('uploaded_by', $file->uploaded_by)])
                    @error('uploaded_by')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Settings -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Settings</h3>

                  <!-- Protection -->
                  <div class="mb-4">
                    <label for="is_protected" class="block text-sm font-medium text-gray-700 mb-1">Protection</label>
                    <select
                      name="is_protected"
                      id="is_protected"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('is_protected') border-red-500 @enderror"
                    >
                      <option value="0" {{ (old('is_protected', $file->is_protected) == 0) ? 'selected' : '' }}>Unprotected</option>
                      <option value="1" {{ (old('is_protected', $file->is_protected) == 1) ? 'selected' : '' }}>Protected</option>
                    </select>
                    @error('is_protected')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- List Type -->
                  <div class="mb-4">
                    <label for="list_type" class="block text-sm font-medium text-gray-700 mb-1">List Type</label>
                    <input
                      type="text"
                      name="list_type"
                      id="list_type"
                      value="{{ old('list_type', $file->list_type) }}"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('list_type') border-red-500 @enderror"
                      placeholder="e.g., SW, GW, SEDIMENT"
                    >
                    @error('list_type')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Matrice DCT -->
                  <div>
                    <label for="matrice_dct" class="block text-sm font-medium text-gray-700 mb-1">Matrice DCT</label>
                    <input
                      type="number"
                      name="matrice_dct"
                      id="matrice_dct"
                      value="{{ old('matrice_dct', $file->matrice_dct) }}"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('matrice_dct') border-red-500 @enderror"
                    >
                    @error('matrice_dct')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Statistics -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistics</h3>

                  <!-- Number of Records -->
                  <div class="mb-4">
                    <label for="number_of_records" class="block text-sm font-medium text-gray-700 mb-1">Number of Records</label>
                    <input
                      type="number"
                      name="number_of_records"
                      id="number_of_records"
                      value="{{ old('number_of_records', $file->number_of_records ?? 0) }}"
                      min="0"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('number_of_records') border-red-500 @enderror"
                    >
                    @error('number_of_records')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Analysis Number -->
                  <div>
                    <label for="analysis_number" class="block text-sm font-medium text-gray-700 mb-1">Analysis Number</label>
                    <input
                      type="number"
                      name="analysis_number"
                      id="analysis_number"
                      value="{{ old('analysis_number', $file->analysis_number) }}"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('analysis_number') border-red-500 @enderror"
                    >
                    @error('analysis_number')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Column 3 - ID Ranges -->
              <div class="space-y-6">
                <!-- Rescan Button -->
                @if(!$isCreate && $file->database_entity_id)
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Rescan Main Table</h3>
                    <p class="text-xs text-gray-500 mb-4">Recalculate main_id_from, main_id_to, and number_of_records from the database.</p>
                    <form action="{{ route('files.rescan', $file) }}" method="POST" class="inline">
                      @csrf
                      <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition" onclick="return confirm('Rescan this file? This will update the ID range and record count.')">
                        Rescan Records
                      </button>
                    </form>
                  </div>
                @endif

                <!-- Main ID Range -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Main ID Range</h3>
                  <p class="text-xs text-gray-500 mb-4">ID range from *_main tables (empodat_main, indoor_main, etc.)</p>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label for="main_id_from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                      <input
                        type="number"
                        name="main_id_from"
                        id="main_id_from"
                        value="{{ old('main_id_from', $file->main_id_from) }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('main_id_from') border-red-500 @enderror"
                      >
                      @error('main_id_from')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                    <div>
                      <label for="main_id_to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                      <input
                        type="number"
                        name="main_id_to"
                        id="main_id_to"
                        value="{{ old('main_id_to', $file->main_id_to) }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('main_id_to') border-red-500 @enderror"
                      >
                      @error('main_id_to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  </div>
                </div>

                <!-- Source ID Range -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Source ID Range</h3>

                  <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                      <label for="source_id_from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                      <input
                        type="number"
                        name="source_id_from"
                        id="source_id_from"
                        value="{{ old('source_id_from', $file->source_id_from) }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('source_id_from') border-red-500 @enderror"
                      >
                      @error('source_id_from')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                    <div>
                      <label for="source_id_to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                      <input
                        type="number"
                        name="source_id_to"
                        id="source_id_to"
                        value="{{ old('source_id_to', $file->source_id_to) }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('source_id_to') border-red-500 @enderror"
                      >
                      @error('source_id_to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  </div>

                  <div>
                    <label for="source_number" class="block text-sm font-medium text-gray-700 mb-1">Source Number</label>
                    <input
                      type="number"
                      name="source_number"
                      id="source_number"
                      value="{{ old('source_number', $file->source_number) }}"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('source_number') border-red-500 @enderror"
                    >
                    @error('source_number')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Method ID Range -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Method ID Range</h3>

                  <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                      <label for="method_id_from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                      <input
                        type="number"
                        name="method_id_from"
                        id="method_id_from"
                        value="{{ old('method_id_from', $file->method_id_from) }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('method_id_from') border-red-500 @enderror"
                      >
                      @error('method_id_from')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                    <div>
                      <label for="method_id_to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                      <input
                        type="number"
                        name="method_id_to"
                        id="method_id_to"
                        value="{{ old('method_id_to', $file->method_id_to) }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('method_id_to') border-red-500 @enderror"
                      >
                      @error('method_id_to')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  </div>

                  <div>
                    <label for="method_number" class="block text-sm font-medium text-gray-700 mb-1">Method Number</label>
                    <input
                      type="number"
                      name="method_number"
                      id="method_number"
                      value="{{ old('method_number', $file->method_number) }}"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('method_number') border-red-500 @enderror"
                    >
                    @error('method_number')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- File Info (edit mode only) -->
                @if(!isset($isCreate) || !$isCreate)
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">File Information</h3>
                    <div class="space-y-2 text-sm">
                      <div class="flex justify-between">
                        <span class="text-gray-600">File ID:</span>
                        <span class="font-mono font-semibold text-gray-900">{{ $file->id }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Upload Date:</span>
                        <span class="font-medium">{{ $file->uploaded_at ? $file->uploaded_at->format('Y-m-d H:i') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Uploaded By:</span>
                        <span class="font-medium">{{ $file->uploader ? $file->uploader->first_name . ' ' . $file->uploader->last_name : 'Unknown' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        @if($file->is_deleted)
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-800">Deleted</span>
                        @else
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                        @endif
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Protected:</span>
                        @if($file->is_protected)
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Yes</span>
                        @else
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">No</span>
                        @endif
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
              <a
                href="{{ route('files.index') }}"
                class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition"
              >
                Cancel
              </a>
              <button
                type="submit"
                class="btn-submit"
              >
                @if(isset($isCreate) && $isCreate)
                  Upload File
                @else
                  Update File
                @endif
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
