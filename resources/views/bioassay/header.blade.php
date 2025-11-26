<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Bioassays
  </span>

  <x-nav-link-header :href="route('bioassayhome.index')" :active="request()->is('*home*')">
    Home
  </x-nav-link-header>

  @if(request()->routeIs('bioassay.search.show'))
  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed"
        title="You are viewing a record in a new tab. To return to search results, close this tab or use your browser's back button."
        aria-label="Search is disabled on this page">
    Search
  </span>
  @else
  <x-nav-link-header :href="route('bioassay.search.filter')" :active="request()->is('*bioassay.search*')">
    Search
  </x-nav-link-header>
  @endif

  <x-nav-link-header :href="route('templates.specific.index', ['code' => 'bioassay'])" :active="request()->is('backend/templates/entity/bioassay*')">
    DCT Download
  </x-nav-link-header>

  <x-nav-link-header :href="route('bioassay.statistics.index')" :active="request()->is('bioassays/statistics*')">
    Statistics
  </x-nav-link-header>

  @role('super_admin')
  <x-nav-link-header :href="route('querylog.index', ['module' => 'bioassay'])" :active="request()->is('*querylog*')">
    History of search
  </x-nav-link-header>
  @else
  <x-nav-link-header>
    History of search <i class="fas fa-lock ml-2"></i>
  </x-nav-link-header>
  @endrole

  {{-- @if(request()->is('*filter') == true)
  <x-nav-link-header :href="route('substances.index')" :active="request()->is('*search')">
    Full View
  </x-nav-link-header>
  @else
  <x-nav-link-header :href="request()->fullUrl()" :active="request()->is('*search')">
    Current View
  </x-nav-link-header>
  @endif   --}}

  {{-- <x-nav-link-header :href="route('duplicates.index')" :active="request()->is('*duplicates*')">
    Duplicates
  </x-nav-link-header>

  @if(request()->routeIs('substances.show') == true)
  <x-nav-link-header :href="route('substances.show', $substance->id)" :active="request()->routeIs('substances.show')">
    Showing Specific Substance
  </x-nav-link-header>
  @endif --}}
</div>
