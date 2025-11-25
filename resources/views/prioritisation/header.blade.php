<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Prioritisation module:
  </span>

  @if(request()->routeIs('prioritisation.monitoring-scarce.show'))
  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed"
        title="You are viewing a record in a new tab. Close this tab to return to search results.">
    Monitoring Scarce
  </span>
  @else
  <x-nav-link-header :href="route('prioritisation.monitoring-scarce.index')"
  :active="request()->routeIs('prioritisation.monitoring-scarce.index')">
  Monitoring Scarce
  </x-nav-link-header>
  @endif

  @if(request()->routeIs('prioritisation.monitoring-danube.show'))
  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed"
        title="You are viewing a record in a new tab. Close this tab to return to search results.">
    Monitoring Danube
  </span>
  @else
  <x-nav-link-header :href="route('prioritisation.monitoring-danube.index')"
  :active="request()->routeIs('prioritisation.monitoring-danube.index')">
  Monitoring Danube
  </x-nav-link-header>
  @endif

  @if(request()->routeIs('prioritisation.modelling-scarce.show'))
  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed"
        title="You are viewing a record in a new tab. Close this tab to return to search results.">
    Modelling Scarce
  </span>
  @else
  <x-nav-link-header :href="route('prioritisation.modelling-scarce.index')"
  :active="request()->routeIs('prioritisation.modelling-scarce.index')">
  Modelling Scarce
  </x-nav-link-header>
  @endif

  @if(request()->routeIs('prioritisation.modelling-danube.show'))
  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed"
        title="You are viewing a record in a new tab. Close this tab to return to search results.">
    Modelling Danube
  </span>
  @else
  <x-nav-link-header :href="route('prioritisation.modelling-danube.index')"
  :active="request()->routeIs('prioritisation.modelling-danube.index')">
  Modelling Danube
  </x-nav-link-header>
  @endif

  {{-- <x-nav-link-header :href="route('prioritisation.dctitems.index')" :active="request()->is('*dctitems*')">
    DCT Download
  </x-nav-link-header> --}}

  @role('super_admin')
  {{-- <x-nav-link-header :href="route('prioritisation.querylog.index')" :active="request()->is('*querylog*')">
    History of search
  </x-nav-link-header> --}}
  @else
  {{-- <x-nav-link-header>
    History of search <i class="fas fa-lock ml-2"></i>
  </x-nav-link-header> --}}
  @endrole
</div>

