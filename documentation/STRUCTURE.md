# Project Structure (Platform vs Tenant Sites)

This repository is the **platform** (Laravel app). The tenant websites are built from **themes + database content**.

## Platform (this repo)
- `app/` Laravel app logic (models, controllers, services)
- `resources/` views + admin UI (React)
- `routes/` API + web routes
- `public/` public entrypoint
- `infrastructure/` provisioning + install scripts
- `storage/` runtime files, tenant themes, nginx configs

## Tenant websites
Tenant sites do **not** live as separate folders at the repo root. They are composed from:
- **Themes**: `storage/themes/<theme_key>/` (uploaded ZIPs are extracted here)
- **Default theme views**: `resources/views/tenant/`
- **Content**: stored in the database (articles, recipes, settings)
- **Nginx vhosts**: generated in `storage/app/nginx/sites-available`

## Where to put custom themes
- Upload ZIP from the admin UI (Templates Manager)
- Or drop a theme in `storage/themes/<key>/` with a Blade view like `home.blade.php`

## What to deploy
On production, you only need the platform (this repo) + DB. Tenant websites are rendered from themes and data.
