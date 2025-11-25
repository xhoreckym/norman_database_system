<!DOCTYPE html>
<html lang="en" style="margin: 0; padding: 0;">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Your Empodat Suspect CSV Export Is Ready</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f6; font-family: Arial, sans-serif;">
  <!-- Container -->
  <div style="max-width: 600px; margin: 40px auto; background-color: #ffffff; padding: 20px; border-radius: 6px; color: #333; line-height: 1.6;">

    @if(isset($messageContent['export_failed']) && $messageContent['export_failed'])
    <!-- Error Heading -->
    <h2 style="margin-top: 0; margin-bottom: 16px; font-weight: normal; color: #e63946;">
      Empodat Suspect Export Processing Error
    </h2>

    <!-- Error Message -->
    <p style="margin-bottom: 16px;">
      We encountered an error while processing your Empodat Suspect export request:
    </p>

    <div style="background-color: #f8d7da; border-left: 4px solid #e63946; padding: 12px; margin-bottom: 24px;">
      <p style="margin: 0; color: #721c24;">
        <strong>Error:</strong> {{ $messageContent['error'] ?? 'Unknown error occurred during export' }}
      </p>
    </div>

    <p style="margin-bottom: 16px;">
      Please try again or contact technical support if the issue persists.
    </p>

    @else
    <!-- Success Heading -->
    <h2 style="margin-top: 0; margin-bottom: 16px; font-weight: normal; color: #2A9D8F;">
      Your Empodat Suspect CSV Export Is Ready!
    </h2>

    <!-- Intro Paragraph -->
    <p style="margin-bottom: 16px;">
      We're pleased to let you know that your requested Empodat Suspect CSV export is now available.
      Click the button below to download your file:
    </p>

    <!-- Export Details -->
    <div style="background-color: #e9f7f6; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
      <p style="margin: 0 0 8px 0;"><strong>File:</strong> {{ $messageContent['filename'] ?? 'Export file' }}</p>
      <p style="margin: 0 0 8px 0;"><strong>File Size:</strong> {{ $messageContent['file_size'] ?? 'Unknown' }}</p>
      @if(isset($messageContent['total_records']))
      <p style="margin: 0 0 8px 0;"><strong>Records:</strong> {{ number_format($messageContent['total_records']) }}</p>
      @endif
      @if(isset($messageContent['processing_time']))
      <p style="margin: 0;"><strong>Processing Time:</strong> {{ $messageContent['processing_time'] }} seconds</p>
      @endif
    </div>

    <!-- Info about matrix metadata -->
    <div style="background-color: #fff3cd; padding: 12px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
      <p style="margin: 0; font-size: 13px; color: #856404;">
        <strong>Note:</strong> This export includes all available matrix metadata (biota, sediments, water, soil, air, etc.) for comprehensive data analysis.
      </p>
    </div>

    <!-- Download Button -->
    <p style="margin-bottom: 24px;">
      <a href="{{ $messageContent['download_link'] ?? '#' }}"
         style="display: inline-block; padding: 12px 24px; background-color: #2A9D8F; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 14px;">
        Download CSV
      </a>
    </p>

    <!-- Expiration Notice -->
    <p style="font-size: 13px; color: #666; margin-bottom: 24px;">
      Note: This download link will be available for the next 24 hours.
    </p>
    @endif

    <!-- Footer / Signature -->
    <p style="margin-bottom: 0; border-top: 1px solid #eee; padding-top: 20px;">
      Thank you,<br>
      <strong>NORMAN Database Team</strong>
    </p>
  </div>
</body>
</html>
