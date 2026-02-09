# Cloudflare Ruleset (Recommended)

Apply these as Transform/Cache/Security rules per zone. Keep API paths bypassed.

## Performance
- HTTP/3 + TLS 1.3: enable in “Network”.
- Brotli: enable (compression level auto).
- Rocket Loader: keep **off** for React/Blade to avoid JS issues.
- Early Hints: enable if available.

## Cache Rules
1) Cache static
   - IF `http.request.uri.path` matches `*.{css,js,mjs,json,map,woff,woff2,ttf,otf,eot,svg,svgz,jpg,jpeg,png,gif,webp,ico,avif}`
   - THEN Cache level: Cache Everything, Edge TTL: 1 month, Respect origin headers (origin already sets 1y).

2) Bypass API/admin
   - IF path starts with `/api/` OR `/admin`
   - THEN Cache level: Bypass.

3) HTML short cache (optional)
   - IF path not matching static or `/api/`
   - THEN Cache level: Cache Everything, Edge TTL: 120s, Origin Cache: Respect headers.

## Security
- WAF: turn on “Bot Fight Mode” (light), “OWASP Core Ruleset”, and rate-limit `/api/*` to 200 req/5m per IP (adjust to traffic).
- Firewall rule: block `CF-Visitor` missing HTTP/3? Not needed; keep HTTP/2 fallback.

## DNS/SSL
- Use proxied (orange cloud) for all tenant domains.
- SSL mode: Full (Strict) with Certbot-issued certs.
- Always Use HTTPS + HSTS (6 months, includeSubDomains optional).

## Logs/Analytics
- Enable “Traffic analytics” or Logpush to R2/S3 for dashboards.
