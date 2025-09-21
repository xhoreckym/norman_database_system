<?php
// resources/views/components/google-analytics.blade.php

@if(config('services.google_analytics.tracking_id') && app()->environment('production'))
<script>
// Only load Google Analytics if consent is given or not yet decided
if (document.cookie.indexOf('analytics_consent=declined') === -1) {
  // Load Google Analytics script
  (function() {
    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.tracking_id') }}';
    document.head.appendChild(script);
    
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', '{{ config('services.google_analytics.tracking_id') }}', {
      // GDPR compliant settings - no personal data
      anonymize_ip: true,
      allow_google_signals: false,
      allow_ad_personalization_signals: false,
      send_page_view: true
    });
  })();
}
</script>
@endif