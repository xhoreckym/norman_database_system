<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      @role(['admin', 'super_admin'])
        <!-- 3-column layout for admin/super_admin -->
        <div class="grid lg:grid-cols-3 gap-6">
          
          <div class="">
            <!-- FIRST COLUMN: Database Entities (All Users) -->
            @include('dashboard.partials.column_1')
          </div>
          

          <div class="">
            <!-- SECOND COLUMN: Templates & Messages (Admin/Super Admin) -->
            @include('dashboard.partials.column_2')
          </div>

          <div class="">
            <!-- THIRD COLUMN: Admin Tools (Super Admin Only) -->
            @include('dashboard.partials.column_3')
          </div>

        </div>
      @else
        <!-- 2-column layout for regular users -->
        <div class="grid lg:grid-cols-2 gap-6">
          
          <div class="">
            <!-- FIRST COLUMN: Database Entities (All Users) -->
            @include('dashboard.partials.column_1')
          </div>
          

          <div class="">
            <!-- SECOND COLUMN: API Access & Templates for regular users -->
            @include('dashboard.partials.column_2')
          </div>

        </div>
      @endrole
    </div>
  </div>
</x-app-layout>