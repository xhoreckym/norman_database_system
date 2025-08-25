<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Substance Database
  </span>

  <x-nav-link-header :href="route('substances.filter')" :active="request()->is('*filter')">
    Filter
  </x-nav-link-header>

  @if(request()->is('*filter'))
    <x-nav-link-header :href="route('substances.index')" :active="request()->is('*search')">
      Full View
    </x-nav-link-header>
  @else
    <x-nav-link-header :href="request()->fullUrl()" :active="request()->is('*search')">
      Current View
    </x-nav-link-header>
  @endif

  @auth
    <x-nav-link-header :href="route('duplicates.index')" :active="request()->is('*duplicates*')">
      Duplicates
    </x-nav-link-header>
  @endauth

  <x-nav-link-header :href="route('susdat.batch.index')" :active="request()->is('*batch*')">
    Batch Conversion
  </x-nav-link-header>

  @auth
    @if(auth()->user()->hasRole(['super_admin', 'admin', 'susdat']))
      <x-nav-link-header :href="route('substances.audited')" :active="request()->routeIs('substances.audited')">
        View all changes (SUSDAT Audit)
      </x-nav-link-header>
    @endif
  @endauth

  {{-- Substance Detail Indicator --}}
  @if(request()->routeIs('substances.show'))
    <div class="ml-auto flex items-center space-x-2 text-gray-600">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <span class="text-sm">Viewing: {{ $substance->display_name ?? $substance->name }}</span>
      @if($substance->prefixed_code)
        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">{{ $substance->prefixed_code }}</span>
      @endif
    </div>
  @endif
</div>
