<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Literature Chemical Exposure Database
  </span>

  <x-nav-link-header :href="route('literature.home.index')" :active="request()->is('*home*')">
    Home
  </x-nav-link-header>

  <x-nav-link-header :href="route('literature.search.filter')" :active="request()->is('*search*')">
    Search
  </x-nav-link-header>

  <x-nav-link-header :href="route('templates.specific.index', ['code' => 'literature'])" :active="request()->is('backend/templates/entity/literature*')">
    DCT Download
  </x-nav-link-header>

  @role('super_admin')
  <x-nav-link-header :href="route('querylog.index', ['module' => 'literature'])" :active="request()->is('*querylog*')">
    History of search
  </x-nav-link-header>
  @else
  <x-nav-link-header>
    History of search <i class="fas fa-lock ml-2"></i>
  </x-nav-link-header>
  @endrole
</div>
@include('literature.helper_lookup_tables')

