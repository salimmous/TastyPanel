@props(['tenant'])

@php
    $branding = app(\App\Services\WhiteLabelService::class)->getBrandingConfig($tenant);
    $showPoweredBy = app(\App\Services\WhiteLabelService::class)->shouldShowPoweredBy($tenant);
@endphp

<footer class="site-footer">
    @if($tenant->custom_footer)
        <div class="custom-footer">
            {!! $tenant->custom_footer !!}
        </div>
    @else
        <div class="default-footer">
            <p>&copy; {{ date('Y') }} {{ $branding['name'] }}. All rights reserved.</p>
        </div>
    @endif

    @if($showPoweredBy)
        <div class="powered-by">
            <p>Powered by <a href="https://tastypanel.site" target="_blank" rel="noopener">TastyPanel</a></p>
        </div>
    @endif
</footer>

<style>
    .site-footer {
        margin-top: auto;
        padding: 2rem 0;
        text-align: center;
        border-top: 1px solid #e5e7eb;
    }

    .site-footer p {
        margin: 0.5rem 0;
        color: #6b7280;
    }

    .powered-by {
        margin-top: 1rem;
        font-size: 0.875rem;
    }

    .powered-by a {
        color: var(--brand-primary, #3b82f6);
        text-decoration: none;
    }

    .powered-by a:hover {
        text-decoration: underline;
    }
</style>