<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    EMPODAT Suspect
  </span>

  <x-nav-link-header :href="route('empodat_suspect.home.index')" :active="request()->is('*home*')">
    Home
  </x-nav-link-header>

  <x-nav-link-header :href="route('empodat_suspect.search.filter')" :active="request()->is('*search*')">
    Search
  </x-nav-link-header>

  <x-nav-link-header :href="route('empodat_suspect.statistics.index')" :active="request()->is('*statistics*')">
    Statistics
  </x-nav-link-header>

  <x-nav-link-header :href="route('templates.specific.index', ['code' => 'empodat_suspect'])" :active="request()->is('backend/templates/entity/empodat_suspect*')">
    DCT Download
  </x-nav-link-header>

  @role('super_admin')
  <x-nav-link-header :href="route('querylog.index', ['module' => 'empodat_suspect'])" :active="request()->is('*querylog*')">
    History of Search
  </x-nav-link-header>
  @else
  <x-nav-link-header>
    History of Search <i class="fas fa-lock ml-2"></i>
  </x-nav-link-header>
  @endrole
</div>
