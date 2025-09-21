{{-- resources/views/components/google-analytics.blade.php --}}

@if(config('services.google_analytics.tracking_id'))
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.tracking_id') }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ config('services.google_analytics.tracking_id') }}');
</script>
@endif