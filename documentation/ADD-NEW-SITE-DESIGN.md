# Add new site — Design

Design ta3 formulaire **Create Site** (Add new site) f platform. UI jdid: Outfit font, palette warm (amber + stone), cards rounded-2xl.

## Layout

- **Max width:** `max-w-3xl`, centered.
- **Grid:** 2 columns (md): col 1 = description, col 2 = form (col-span-2).
- **Left column:** "Site Configuration" + description + workflow ref.
- **Right column:** form f card (shadow, rounded), bg white, footer gray-50.

## Sections (order)

1. **Site Name** — text input, placeholder "My Project Site".
2. **Primary Domain** — prefix `https://` + input placeholder "example.com", helper: "The primary domain for this tenant."
3. **Database** — heading + info only: "Created automatically during install (MySQL). Credentials are written to the site's .env."
4. **Admin account (site login)** — heading + short description (optional, first admin, seed/first-run). Fields:
   - Admin email (prefill: current user email)
   - Admin username (prefill: "admin")
   - Admin password (placeholder "Min 8 characters", helper: optional, used by seed)
5. **Install after create** — info block (primary-50/50 bg, primary-200 border, download icon): text 3la provisioning auto, redirect, one click.

## Buttons

- **Cancel** — secondary (border gray), link to platform.tenants.
- **Create Site** — primary (bg-primary-600), submit.

## Errors

- Full-width alert (red-50 bg) above form fields, list of validation errors.

## Reference

- View: `resources/views/platform/tenant-create.blade.php`
- Controller: `PlatformController@storeTenant`, `PlatformController@createTenant`
- Workflow: `documentation/TENANT-WORKFLOW.md`, one-click: `documentation/TENANT-ONE-CLICK-INSTALL.md`
