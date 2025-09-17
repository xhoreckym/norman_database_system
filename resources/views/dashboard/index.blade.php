<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full px-4 sm:px-6 lg:px-8">
      @role(['admin', 'super_admin'])
        <!-- 4-column layout for admin/super_admin with server status -->
        <div class="grid lg:grid-cols-4 gap-6">
          
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

          <div class="">
            <!-- FOURTH COLUMN: Server Status (Server Payment Roles Only) -->
            @role(['super_admin', 'server_payment_admin', 'server_payment_viewer'])
              @include('dashboard.partials.column_4')
            @endrole
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