<x-app-layout>
  <x-slot name="header">
    @include('sle.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Suspect List Exchange – NORMAN SLE
          </h1>
          
          <!-- Description -->
          <p class="text-gray-700 leading-relaxed mb-4">
            The NORMAN Suspect List Exchange (NORMAN-SLE) was established in 2015 as a central access point for NORMAN members (and others) to find suspect lists relevant for their environmental monitoring questions. The NORMAN-SLE documents all individual collections that form a part of the merged collection NORMAN SusDat. The original SLE lists should be consulted to verify SusDat information if necessary (see Source column in SusDat). NORMAN-SLE versions are tracked on Zenodo. 
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            NEW: Check out our NORMAN-SLE publication in ESEU @ DOI: 10.1186/s12302-022-00680-6 
          </p>
          
          <p class="text-gray-700 leading-relaxed mb-4">
            Comments and contributions are welcome - please email us at suspects@normandata.eu.
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            Please refer to our documentation pages for: citation instructions, credits, updates, license details, SDFs and other useful tips!
          </p>
          

          <!-- Admin Actions -->
          @role('admin|super_admin|sle')
          <div class="mb-4 flex justify-end">
            <a href="{{ route('sle.sources.create') }}" class="btn-create">
              <i class="fa fa-plus mr-2"></i>
              Add new source
            </a>
          </div>
          @endrole

          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>Description</th>
                <th>Link Full List</th>
                <th>Link InChI Key List</th>
                <th>Link References</th>
                @role('admin|super_admin|sle')
                <th>Actions</th>
                @endrole
              </tr>
            </thead>
            <tbody>
              @foreach ($sleSources as $sle)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                <td class="p-1 text-center">
                  {{ $sle->id }}
                </td>
                <td class="p-1 text-center">
                  {{ $sle->code }}
                </td>
                <td class="p-1 text-center">
                  {{ $sle->name }}
                </td>
                <td class="p-1 text-center">
                  {{ $sle->description }} 
                </td>
                <td class="p-1 text-left">
                  @if($sle->link_full_list)
                    <div class="prose max-w-none">
                      {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $sle->link_full_list) !!}
                    </div>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>
                <td class="p-1 text-left">
                  @if($sle->link_inchikey_list)
                    <div class="prose max-w-none">
                      {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $sle->link_inchikey_list) !!}
                    </div>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>
                <td class="p-1 text-left">
                  @if($sle->link_references)
                    <div class="prose max-w-none">
                      {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $sle->link_references) !!}
                    </div>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>
                @role('admin|super_admin|sle')
                                  <td class="p-1 text-center">
                    <div class="flex space-x-2 justify-center">
                      <a href="{{ route('sle.sources.show', $sle) }}" class="text-blue-600 hover:text-blue-900" title="View">
                        <i class="fa fa-eye"></i>
                      </a>
                      <a href="{{ route('sle.sources.edit', $sle) }}" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                        <i class="fa fa-edit"></i>
                      </a>
                    </div>
                  </td>
                @endrole
              </tr>
              @endforeach
            </tbody>
          </table>

        </div>
        
        
      </div>
    </div>
  </div>
  
</x-app-layout>