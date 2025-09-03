<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Suspect Substance Database
  </span>

  <x-nav-link-header :href="route('substances.search.filter')" :active="request()->routeIs('substances.search.filter')">
    Search
  </x-nav-link-header>

  {{-- Show Results only when a filter is active --}}
  @if (request()->routeIs('substances.search.search') || 
       (request()->has('searchCategory') && request()->input('searchCategory') == 1) ||
       (request()->has('searchSource') && request()->input('searchSource') == 1) ||
       (request()->has('searchSubstance') && request()->input('searchSubstance') == 1))
    <x-nav-link-header :href="request()->fullUrl()" :active="request()->routeIs('substances.search.search')">
      Results
    </x-nav-link-header>
  @else
    {{-- Show List of All Substances when no filter is active --}}
    <x-nav-link-header :href="route('substances.index')" :active="request()->routeIs('substances.index')">
      List of All Substances
    </x-nav-link-header>
  @endif

  @role('super_admin')
    <x-nav-link-header :href="route('querylog.index', ['module' => 'susdat'])" :active="request()->is('*querylog*')">
      History of search
    </x-nav-link-header>
  @else
    <x-nav-link-header>
      History of search <i class="fas fa-lock ml-2"></i>
    </x-nav-link-header>
  @endrole

  @auth
    @if (auth()->user()->hasRole(['super_admin', 'admin', 'susdat']))
      <x-nav-link-header :href="route('substances.audited')" :active="request()->routeIs('substances.audited')">
        Audit Log
      </x-nav-link-header>
    @endif
  @endauth

  @auth
    @if (auth()->user()->hasRole(['super_admin', 'admin', 'susdat']))
      <x-nav-link-header :href="route('duplicates.index')" :active="request()->is('*duplicates*')">
        Duplicates
      </x-nav-link-header>
    @endif
  @endauth

  <x-nav-link-header :href="route('susdat.batch.index')" :active="request()->is('*batch*')">
    Batch Conversion
  </x-nav-link-header>

  {{-- Substance Detail Indicator --}}
  @if (request()->routeIs('substances.show'))
    <div class="ml-auto flex items-center space-x-2 text-gray-600">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <span class="text-sm">Viewing: {{ $substance->display_name ?? $substance->name }}</span>
      @if ($substance->prefixed_code)
        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">{{ $substance->prefixed_code }}</span>
      @endif
    </div>
  @endif
</div>
