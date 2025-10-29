@if(isset($serverPayment) && $serverPayment)
  <div class="space-y-4">
    <!-- Payment Period and Status in same line -->
    <div class="flex justify-between items-center">
      <div>
        <p class="text-sm text-gray-600">Current Period:</p>
        <p class="font-medium">{{ $serverPayment->formatted_period }}</p>
      </div>
      <div class="text-right">
        <p class="text-sm text-gray-600">Status:</p>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
          @if($serverPayment->status === 'paid') bg-green-100 text-green-800
          @elseif($serverPayment->status === 'pending') bg-yellow-100 text-yellow-800
          @else bg-red-100 text-red-800
          @endif">
          {{ ucfirst(str_replace('_', ' ', $serverPayment->status)) }}
        </span>
      </div>
    </div>
    
    <!-- Days Remaining Progress Bar -->
    @if($serverPayment->status === 'paid' && $daysRemaining !== null)
      <div>
        <div class="flex flex-col gap-1 mb-1">
          <div class="flex justify-between text-sm text-gray-600">
            <span>Days Remaining</span>
            <span class="font-medium">{{ $daysRemaining }} days</span>
          </div>
          <span class="text-xs text-gray-500">until {{ $serverPayment->period_end_date->format('Y-m-d') }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div class="h-2 rounded-full
            @if($daysRemaining > 30) bg-green-500
            @elseif($daysRemaining > 14) bg-yellow-500
            @else bg-red-500
            @endif"
            style="width: {{ 100 - $progressPercentage }}%"></div>
        </div>
        @if($daysRemaining <= 14)
          <p class="text-xs text-red-600 mt-1">⚠️ Payment renewal needed soon</p>
        @endif
      </div>
    @endif
  
    
  </div>
@else
  <div class="text-center py-4">
    <p class="text-gray-500 text-sm">No server payment data available</p>
  </div>
@endif
