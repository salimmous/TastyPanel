# Performance & Speed Playbook

Applies to platform panel (`/var/www/tastypanel`) and every tenant site (`/var/www/tastypanel-sites/<tenant>`). Focus is low latency, solid SEO, and light ops overhead.

## Web Server (Nginx)
- Use the vhost stub at `resources/stubs/nginx-vhost.stub` (already includes static caching and short HTML cache).
- Enable HTTP/3 + TLS 1.3 at the server block level (outside this stub) when Nginx build supports it.
- Turn on `gzip` (safe defaults) if Cloudflare is not fronting the site. Prefer Cloudflare Brotli when available.
- Set `client_max_body_size 50M` unless a tenant needs bigger uploads.

## PHP-FPM
- Per-tenant pool; set `pm = dynamic`, size it from RAM (baseline: `pm.max_children = 8`, `pm.max_requests = 500`).
- Enable Opcache: `opcache.enable=1`, `opcache.memory_consumption=192`, `opcache.validate_timestamps=0` in production.
- Templates ready: `platform-config/php-fpm/pool.conf.example` and `platform-config/php-fpm/opcache.ini.example`.

## Laravel App
- Cache/store/session: prefer Redis in production (`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`).
- Config and route cache after deploy: `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
- Queue everything non-blocking (mail, AI generation, webhooks). Keep horizon-like monitoring via existing queue UI.
- DB indices: make sure `slug`, `status`, `created_at`, and FK columns are indexed on `articles`, `recipes`, `domains`, `tenants`.
- Use pagination for heavy lists; avoid N+1 with `with()` on article/recipe listings.

## Static Assets & Frontend
- Build with Vite production mode; split chunks; lazy-load admin views.
- Images: serve WebP/AVIF variants; constrain max width; use `loading="lazy"` on non-hero media.
- Fonts: self-hosted, `preload` primary weights, `font-display: swap`.
- Add `<link rel="preconnect">` to CDN and API origins in the main Blade layout.

## Caching Rules
- HTML shell cache: 60–120s (already set in the Nginx stub).
- Static assets: 1 year immutable (in stub).
- API responses: `Cache-Control: no-store` (in stub) to keep admin/API fresh.
- CDN (Cloudflare): cache static only; bypass `/api/*`; enable “cache everything” only if origin honors `Cache-Control`.

## SEO (per tenant)
- Generate `sitemap.xml` and `robots.txt` per tenant; ping Google/Bing after publish.
- Add canonical URLs, OpenGraph/Twitter cards, and Recipe Schema to theme layouts.
- Return 410/301 for removed content to avoid soft-404 issues.

## Monitoring & Health
- Enable HTTP/3 and TLS checks (already wired in the platform health service).
- Alerts on: SSL expiry <15d, queue failures, slow queries (>500ms), 5xx rate spikes.
- Log rotation: keep 7–14 days; ship to centralized store if available.

## Backups
- Daily per-tenant backups (DB + files) to S3-compatible storage; retain 7–30 days with rotation.
- Verify restores regularly via `restore-tenant.sh` into staging instances.

## Deployment Checklist
1) `composer install --no-dev` and `npm run build`.
2) `php artisan migrate --force`.
3) Clear+rebuild caches: `php artisan optimize`.
4) Reload Nginx + PHP-FPM pools for changed tenants only.
