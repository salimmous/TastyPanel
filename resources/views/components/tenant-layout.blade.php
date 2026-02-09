@props(['tenant'])

@php
    $analytics = app(\App\Services\AnalyticsService::class);
    $whiteLabel = app(\App\Services\WhiteLabelService::class);
    $branding = $whiteLabel->getBrandingConfig($tenant);
    $trackingScript = $analytics->getTrackingScript($tenant);
    $cssVariables = $whiteLabel->getCssVariables($tenant);
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Favicon --}}
    <link rel="icon" href="{{ $branding['favicon'] }}">

    {{-- Brand CSS Variables --}}
    <style>
        {!! $cssVariables !!}
    </style>

    {{-- Custom CSS --}}
    @if($branding['custom_css'])
        <style>
            {!! $branding['custom_css'] !!}
        </style>
    @endif

    {{-- Analytics --}}
    @if($trackingScript)
        {!! $trackingScript !!}
    @endif

    {{ $head ?? '' }}
</head>

<body>
    {{ $slot }}

    {{-- Custom JS --}}
    @if($branding['custom_js'])
        <script>
            {!! $branding['custom_js'] !!}
        </script>
    @endif

    {{ $scripts ?? '' }}
</body>

</html>