@role('super_admin|admin')
<div class="bg-amber-50 border-t border-b border-amber-200 py-2 px-4 sm:px-6 lg:px-8">
  <div class="flex flex-wrap items-center">
    <span class="text-xs font-semibold text-amber-800 uppercase tracking-wide mr-4 mb-1 sm:mb-0">
      <i class="fas fa-database mr-1"></i> Helper Lookup Tables (Admin Only)
    </span>

    <div class="flex flex-wrap items-center gap-1">
      <a href="{{ route('literature.life_stages.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*life_stages*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Life Stages
      </a>

      <a href="{{ route('literature.habitat_types.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*habitat_types*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Habitat Types
      </a>

      <a href="{{ route('literature.concentration_units.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*concentration_units*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Concentration Units
      </a>

      <a href="{{ route('literature.common_names.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*common_names*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Common Names
      </a>

      <a href="{{ route('literature.species.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*species*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Species
      </a>

      <a href="{{ route('literature.biota_sexs.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*biota_sexs*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Biota Sex
      </a>

      <a href="{{ route('literature.tissues.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*tissues*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Tissues
      </a>

      <a href="{{ route('literature.type_of_numeric_quantities.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*type_of_numeric_quantities*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Type of Numeric Quantity
      </a>

      <a href="{{ route('literature.use_categories.index') }}"
         class="text-xs px-3 py-1 rounded-md {{ request()->is('*use_categories*') ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 hover:bg-amber-100' }} border border-amber-300 transition">
        Use Categories
      </a>
    </div>
  </div>
</div>
@endrole
