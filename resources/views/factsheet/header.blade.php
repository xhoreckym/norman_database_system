<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Substance Factsheets
  </span>


  <x-nav-link-header :href="route('factsheets.home.index')" :active="request()->is('*home*')">
    Home
  </x-nav-link-header>

  <x-nav-link-header :href="route('factsheets.search.filter')" :active="request()->is('*search*')">
    Search Data
  </x-nav-link-header>

  @role('super_admin')
  <x-nav-link-header :href="route('querylog.index', ['module' => 'empodat'])" :active="request()->is('*querylog*')">
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