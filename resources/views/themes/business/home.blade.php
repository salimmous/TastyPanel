@extends('themes.layout')

@push('styles')
    <style>
        .biz-hero {
            padding: 5rem 0 4rem;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 64, 175, 0.85));
            color: #f8fafc;
        }

        .biz-hero-grid {
            display: grid;
            gap: 3rem;
            align-items: center;
        }

        @media (min-width: 960px) {
            .biz-hero-grid {
                grid-template-columns: 1.05fr 0.95fr;
            }
        }

        .biz-title {
            font-size: clamp(2.6rem, 4vw, 3.8rem);
            margin: 1rem 0 1.5rem;
            line-height: 1.1;
        }

        .biz-panel {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 24px;
            padding: 1.6rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .biz-metrics {
            display: grid;
            gap: 1rem;
            margin-top: 2rem;
        }

        @media (min-width: 640px) {
            .biz-metrics {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .biz-metric {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            color: #0f172a;
        }

        .biz-stack {
            display: grid;
            gap: 1.2rem;
        }

        .biz-content-grid {
            display: grid;
            gap: 1.5rem;
        }

        @media (min-width: 860px) {
            .biz-content-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
@endpush

@section('content')
    <header class="container" style="display: flex; align-items: center; justify-content: space-between; padding: 1.6rem 0;">
        <div style="font-weight: 700; font-size: 1.2rem;">
            {{ $settings['brand_name'] ?? $tenant->name }}
        </div>
        <nav style="display: flex; gap: 1.5rem; font-size: 0.95rem; color: var(--text-muted);">
            <a href="#">Solutions</a>
            <a href="#">Case studies</a>
            <a href="#">Pricing</a>
            <a href="#">Contact</a>
        </nav>
    </header>

    <section class="biz-hero">
        <div class="container biz-hero-grid">
            <div>
                <span class="pill" style="background: rgba(255, 255, 255, 0.12); color: #f8fafc;">
                    {{ $settings['tagline'] ?? 'Multi-domain platform' }}
                </span>
                <h1 class="biz-title">
                    {{ $settings['hero_title'] ?? 'Launch and manage niche sites at scale.' }}
                </h1>
                <p style="color: rgba(248, 250, 252, 0.75); max-width: 520px;">
                    {{ $settings['hero_subtitle'] ?? 'Your mother platform provisions domains, installs tailored themes, and keeps every website synced.' }}
                </p>
                <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a class="button" href="#" style="background: #f8fafc; color: #0f172a;">{{ $settings['primary_cta'] ?? 'Request a demo' }}</a>
                    <a class="button secondary" href="#" style="border-color: rgba(248, 250, 252, 0.4); color: #f8fafc;">{{ $settings['secondary_cta'] ?? 'View platforms' }}</a>
                </div>
            </div>

            <div class="biz-stack">
                <div class="biz-panel">
                    <p style="text-transform: uppercase; letter-spacing: 0.18em; font-size: 0.7rem; color: rgba(248, 250, 252, 0.6);">
                        Platform status
                    </p>
                    <h3 style="margin: 0.6rem 0 1.2rem; font-size: 1.4rem;">
                        {{ $settings['spotlight_title'] ?? 'All domains synced' }}
                    </h3>
                    <div style="display: grid; gap: 0.8rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span>Automation</span>
                            <strong>Active</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span>DNS & SSL</span>
                            <strong>Provisioned</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span>Themes</span>
                            <strong>12 templates</strong>
                        </div>
                    </div>
                </div>
                <div class="biz-panel">
                    <p style="text-transform: uppercase; letter-spacing: 0.18em; font-size: 0.7rem; color: rgba(248, 250, 252, 0.6);">
                        Automation
                    </p>
                    <p style="margin: 0.6rem 0 0; font-size: 1rem;">
                        Auto-install niche themes when a new domain is connected, plus instant reporting to the admin hub.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="container" style="margin: -3rem auto 3rem;">
        <div class="biz-metrics">
            <div class="biz-metric">
                <p style="text-transform: uppercase; letter-spacing: 0.16em; font-size: 0.7rem; color: var(--text-muted);">Domains</p>
                <h3 style="margin: 0.4rem 0 0;">{{ $settings['metric_domains'] ?? '48' }}</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Connected and active</p>
            </div>
            <div class="biz-metric">
                <p style="text-transform: uppercase; letter-spacing: 0.16em; font-size: 0.7rem; color: var(--text-muted);">Templates</p>
                <h3 style="margin: 0.4rem 0 0;">{{ $settings['metric_templates'] ?? '14' }}</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Ready to deploy</p>
            </div>
            <div class="biz-metric">
                <p style="text-transform: uppercase; letter-spacing: 0.16em; font-size: 0.7rem; color: var(--text-muted);">Provisioning</p>
                <h3 style="margin: 0.4rem 0 0;">{{ $settings['metric_time'] ?? '90s' }}</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Average setup</p>
            </div>
        </div>
    </section>

    <section class="container" style="margin-bottom: 4rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.6rem;">
            <div>
                <h3 style="margin: 0; font-size: 1.6rem;">Latest Articles</h3>
                <p style="color: var(--text-muted); margin: 0.4rem 0 0;">Synced per domain</p>
            </div>
            <span class="pill">Auto publish</span>
        </div>
        <div class="biz-content-grid">
            @forelse ($articles as $article)
                <div class="card" style="display: grid; gap: 0.8rem;">
                    <span style="text-transform: uppercase; letter-spacing: 0.18em; font-size: 0.7rem; color: var(--text-muted);">
                        Insight
                    </span>
                    <strong style="font-size: 1.05rem;">{{ $article->title }}</strong>
                    <p style="color: var(--text-muted); margin: 0;">
                        {{ \Illuminate\Support\Str::limit($article->description, 120) }}
                    </p>
                </div>
            @empty
                <div class="card">
                    <p style="color: var(--text-muted); margin: 0;">No articles yet for this tenant.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
