@extends('themes.layout')

@push('styles')
    <style>
        .food-hero {
            position: relative;
            padding: 5.5rem 0 4.5rem;
            background: radial-gradient(circle at top left, rgba(249, 115, 22, 0.18), transparent 60%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.18), transparent 55%),
                #ffffff;
            overflow: hidden;
        }

        .food-hero::after {
            content: '';
            position: absolute;
            right: -12%;
            top: 12%;
            width: 340px;
            height: 340px;
            background: linear-gradient(140deg, rgba(249, 115, 22, 0.2), rgba(15, 118, 110, 0.1));
            border-radius: 32% 68% 70% 30% / 40% 30% 70% 60%;
            z-index: 0;
        }

        .food-hero-grid {
            display: grid;
            gap: 3rem;
            align-items: center;
        }

        @media (min-width: 960px) {
            .food-hero-grid {
                grid-template-columns: 1.1fr 0.9fr;
            }
        }

        .food-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.8rem, 4vw, 4rem);
            margin: 1rem 0 1.5rem;
            line-height: 1.1;
        }

        .food-subtitle {
            font-size: 1.05rem;
            color: var(--text-muted);
        }

        .food-feature {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.2rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .food-feature span {
            font-weight: 600;
        }

        .food-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: -3rem;
        }

        @media (min-width: 860px) {
            .food-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .food-card-title {
            font-weight: 600;
            margin: 1rem 0 0.5rem;
        }

        .food-list {
            display: grid;
            gap: 1.2rem;
        }

        @media (min-width: 860px) {
            .food-list {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .food-mini {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }
    </style>
@endpush

@section('content')
    <header class="container" style="display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 0;">
        <div style="font-weight: 700; font-size: 1.2rem;">
            {{ $settings['brand_name'] ?? $tenant->name }}
        </div>
        <nav style="display: flex; gap: 1.5rem; font-size: 0.95rem; color: var(--text-muted);">
            <a href="#">Menu</a>
            <a href="#">Chef</a>
            <a href="#">Blog</a>
            <a href="#">Contact</a>
        </nav>
    </header>

    <section class="food-hero">
        <div class="container food-hero-grid">
            <div style="position: relative; z-index: 1;">
                <span class="pill">{{ $settings['tagline'] ?? 'Signature Food Studio' }}</span>
                <h1 class="food-title">
                    {{ $settings['hero_title'] ?? 'Crafted menus for bold culinary brands.' }}
                </h1>
                <p class="food-subtitle">
                    {{ $settings['hero_subtitle'] ?? 'Launch premium food concepts, publish recipes, and deliver a curated dining experience on every domain.' }}
                </p>
                <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a class="button" href="#">{{ $settings['primary_cta'] ?? 'Launch a Menu' }}</a>
                    <a class="button secondary" href="#">{{ $settings['secondary_cta'] ?? 'See Examples' }}</a>
                </div>
            </div>

            <div class="card" style="position: relative; z-index: 1;">
                <p style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.14em; color: var(--text-muted);">
                    Today spotlight
                </p>
                <h3 style="font-size: 1.5rem; margin: 0.5rem 0 1.5rem;">
                    {{ $settings['spotlight_title'] ?? 'Mediterranean Harvest Bowl' }}
                </h3>
                <div style="display: grid; gap: 0.8rem;">
                    <div class="food-feature">
                        <span>Seasonal ingredients</span>
                        <span style="margin-left: auto; color: var(--text-muted);">Fresh</span>
                    </div>
                    <div class="food-feature">
                        <span>Prep time</span>
                        <span style="margin-left: auto; color: var(--text-muted);">18 min</span>
                    </div>
                    <div class="food-feature">
                        <span>Signature sauce</span>
                        <span style="margin-left: auto; color: var(--text-muted);">Chef pick</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container food-grid">
        <div class="card">
            <span class="pill">Niche ready</span>
            <h4 class="food-card-title">Restaurant Brand Kit</h4>
            <p style="color: var(--text-muted);">
                Plug a new domain and deploy a dining site instantly with menus, offers, and reservation blocks.
            </p>
        </div>
        <div class="card">
            <span class="pill">Auto publish</span>
            <h4 class="food-card-title">Recipe Library</h4>
            <p style="color: var(--text-muted);">
                Import content, tag cuisines, and keep every site updated from the mother platform.
            </p>
        </div>
        <div class="card">
            <span class="pill">Analytics</span>
            <h4 class="food-card-title">Performance Dashboard</h4>
            <p style="color: var(--text-muted);">
                Track clicks, bookings, and trending dishes per domain with shared insights.
            </p>
        </div>
    </section>

    <section class="container" style="margin: 3rem auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <div>
                <h3 style="font-size: 1.6rem; margin: 0;">Latest Recipes</h3>
                <p style="color: var(--text-muted); margin: 0.4rem 0 0;">Freshly added for this domain</p>
            </div>
            <span class="pill">Auto-synced</span>
        </div>
        <div class="food-list">
            @forelse ($recipes as $recipe)
                <div class="card food-mini">
                    <strong>{{ $recipe->title }}</strong>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">
                        {{ $recipe->category?->name ?? 'Recipe' }} • {{ $recipe->prep_time }}
                    </span>
                    <p style="color: var(--text-muted); margin: 0;">
                        {{ \Illuminate\Support\Str::limit($recipe->description, 120) }}
                    </p>
                </div>
            @empty
                <div class="card">
                    <p style="color: var(--text-muted); margin: 0;">No recipes yet for this site.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="container" style="margin-bottom: 4rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <div>
                <h3 style="font-size: 1.6rem; margin: 0;">Top Categories</h3>
                <p style="color: var(--text-muted); margin: 0.4rem 0 0;">Organized by the platform</p>
            </div>
            <span class="pill">Tenant data</span>
        </div>
        <div class="food-list">
            @forelse ($categories as $category)
                <div class="card food-mini">
                    <strong>{{ $category->name }}</strong>
                    <span style="color: var(--text-muted); font-size: 0.9rem;">
                        {{ $category->recipes_count }} recipes
                    </span>
                    <p style="color: var(--text-muted); margin: 0;">
                        {{ \Illuminate\Support\Str::limit($category->description, 110) }}
                    </p>
                </div>
            @empty
                <div class="card">
                    <p style="color: var(--text-muted); margin: 0;">No categories configured yet.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
