<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $settings['brand_name'] ?? $tenant->name }}</title>
        <meta name="description" content="{{ $settings['meta_description'] ?? ($settings['brand_tagline'] ?? $tenant->name) }}">
        <link rel="canonical" href="{{ url()->current() }}" />
        <meta property="og:title" content="{{ $settings['brand_name'] ?? $tenant->name }}" />
        <meta property="og:description" content="{{ $settings['meta_description'] ?? ($settings['brand_tagline'] ?? $tenant->name) }}" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
            rel="stylesheet"
        />
        <style>
            :root {
                --primary: {{ $settings['primary_color'] ?? '#1f2937' }};
                --secondary: {{ $settings['secondary_color'] ?? '#f59e0b' }};
                --accent: {{ $settings['accent_color'] ?? '#f97316' }};
                --text-main: #111827;
                --text-muted: #6b7280;
                --bg: #f8fafc;
                --card: #ffffff;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: 'Space Grotesk', system-ui, -apple-system, sans-serif;
                background: var(--bg);
                color: var(--text-main);
                line-height: 1.6;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            .container {
                width: min(1120px, 92%);
                margin: 0 auto;
            }

            .pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.35rem 0.8rem;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                background: rgba(17, 24, 39, 0.08);
                color: var(--primary);
            }

            .button {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                border-radius: 999px;
                padding: 0.85rem 1.8rem;
                font-size: 0.95rem;
                font-weight: 600;
                background: var(--primary);
                color: white;
                border: none;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
            }

            .button.secondary {
                background: white;
                color: var(--primary);
                border: 1px solid rgba(15, 23, 42, 0.1);
                box-shadow: none;
            }

            .card {
                background: var(--card);
                border-radius: 24px;
                padding: 1.8rem;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
                border: 1px solid rgba(148, 163, 184, 0.2);
            }
        </style>
        @stack('styles')
    </head>
    <body class="theme-{{ $themeKey ?? 'default' }}">
        @yield('content')
    </body>
</html>
