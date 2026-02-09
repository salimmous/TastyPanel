@props(['src', 'alt' => '', 'width' => null, 'height' => null, 'class' => '', 'lazy' => true])

@php
    $optimizer = app(\App\Services\ImageOptimizationService::class);
    $urls = $optimizer->getUrls($src);
    $lazyAttr = $lazy ? 'lazy' : 'eager';
@endphp

<picture class="{{ $class }}">
    {{-- Modern browsers: AVIF (best compression) --}}
    @if(file_exists(public_path($urls['avif'])))
        <source srcset="{{ asset($urls['avif']) }}" type="image/avif">
    @endif

    {{-- Fallback: WebP --}}
    @if(file_exists(public_path($urls['webp'])))
        <source srcset="{{ asset($urls['webp']) }}" type="image/webp">
    @endif

    {{-- Final fallback: Original --}}
    <img src="{{ asset($urls['original']) }}" alt="{{ $alt }}" @if($width) width="{{ $width }}" @endif @if($height)
    height="{{ $height }}" @endif loading="{{ $lazyAttr }}" decoding="async" class="{{ $class }}">
</picture>