# Cloudflare f Platform — wach 3adna w kifahc plan

## Cloudflare m3a l-platform, machi m3a site b7do

**L-configuration dyal Cloudflare** kat-msa f **l-platform 7ta 7da** (f `.env`). Ma 3andekch t-configuri Cloudflare f kol site b 7du — l-panel yst3mel nafs l-token w (optionnel) nafs l-zone_id l **kol** tenant domains. Hadak = **full full, clean**: 7ta 7da source of truth, kolchi khadam m3ana.

---

## Ach no khass t-na3ti (what you give us)

| No | Ach | Kayn fayn t-jbedo |
|----|-----|-------------------|
| 1 | **CLOUDFLARE_TOKEN** | Cloudflare Dashboard → My Profile → API Tokens → Create Token. Template "Edit zone DNS" aw custom: **Zone:Zone:Read**, **Zone:DNS:Edit**. Copy l-token. |
| 2 | **CLOUDFLARE_ZONE_ID** (optionnel) | Ila 3andek zone wa7da l kol domains: Cloudflare Dashboard → Domain → Overview → Zone ID (right sidebar). Ila 3andek bzzaf zones, 7ot empty w zid **cf_zone_id** per domain f Domain Center. |
| 3 | **CLOUDFLARE_DNS_TOKEN** | Nafs l-token (ila 3andu Zone:DNS:Edit) aw token jdad b **Zone:DNS:Edit** 7ta 7da bach Certbot y-zd TLS. Ila CLOUDFLARE_TOKEN 3andu had scope, 7ot f CLOUDFLARE_DNS_TOKEN nafs chi aw 7ellah empty (y-st3mel CLOUDFLARE_TOKEN). |
| 4 | **SSL_CERTBOT_EMAIL** | Email dyalek (Let's Encrypt y-contactik). |

**Résumé:** 7ta token (aw 2 ila bghiti DNS token m-ferré), 7ta zone_id ila kol domains f zone wa7da, 7ta email. **Hadak kolchi — l-platform kay dir ba9i.**

---

## Ach no l-platform kay dir (what we do)

- **7ta 7da f `.env`:** token, zone_id, dns_token, SSL_AUTO, SSL_CERTBOT_EMAIL. Ma 3andekch config f kol site.
- **Provisioning domain (tenant):** l-platform t-create A record 3la Cloudflare (proxied), t-save `cf_zone_id` w `cf_record_id` f table `domains`. Ila ma 3andekch zone_id f .env, khass t-7ot `cf_zone_id` per domain men Domain Center.
- **SSL automatic:** 9bal/ba3d provisioning, Certbot y-khedem b DNS-01 (CLOUDFLARE_DNS_TOKEN) → certificate yt-install. Bla ma t-dir 7aja f Cloudflare b 7du.
- **Domain Center / Runbooks:** purge cache (host aw zone), delete DNS record, DNS inventory — kolha men l-platform b nafs l-credentials.

**Full full:** DNS + SSL + purge + inventory m3a platform 7ta 7da. **Clean:** ma 3amra config per site (illa multi-zone: 7ot cf_zone_id per domain).

---

## SSL automatic — bach tb9a dar automatic (clean)

Bach **SSL tb9a dar automatic** (Certbot DNS-01 3la Cloudflare), khass 3 variables 7ta 3:

| Variable | Obligatoire | Description |
|----------|-------------|-------------|
| `SSL_AUTO` | Iyah | `true` bach l-platform t-provision SSL auto. |
| `SSL_CERTBOT_EMAIL` | Iyah | Email bach Let's Encrypt y-contactik. |
| `CLOUDFLARE_DNS_TOKEN` | Iyah | Token Cloudflare scope **Zone:DNS:Edit** (aw `CLOUDFLARE_TOKEN` ila 3andu had scope). |

**F `.env`:**
```env
SSL_AUTO=true
SSL_CERTBOT_EMAIL=your@email.com
CLOUDFLARE_DNS_TOKEN=your_cloudflare_dns_token
```

Daba: provisioning domain → DNS A record (Cloudflare) → Certbot y-khedem b DNS-01 → certificate yt-install. **Hadak — clean, automatic.**

(Ila ma 3andekch Cloudflare, SSL auto ma ykhedemch; khass token DNS-01.)

---

## Wach Cloudflare 3adna? — Iyah, insert 3adna

L-platform 3andha **Cloudflare** m-integré b:

### 1) Config (`.env`)

```env
CLOUDFLARE_TOKEN=          # API token (Zone + DNS)
CLOUDFLARE_ZONE_ID=        # Zone par défaut (optionnel si chaque domain 3andu cf_zone_id)
CLOUDFLARE_DNS_TOKEN=      # Token DNS-01 bach Certbot y-provision SSL (fallback: CLOUDFLARE_TOKEN)
```

- `config/services.php` → `services.cloudflare` (token, zone_id, target_ip, dns_token).

### 2) Service

- **`app/Services/CloudflareService.php`**:
  - **DNS:** `listDnsRecords`, `getDnsRecord`, `createARecord`, `deleteDnsRecord`
  - **Cache:** `purgeCache` (host aw zone), `setCacheLevel`, `createPageRule` (cache)
  - Utilisé f: provisioning (DNS A record), SSL (Certbot DNS-01), Domain Center (DNS inventory), runbooks (purge, delete DNS)

### 3) Kayn fayn f l-platform

| Feature | Kayn fayn |
|--------|-----------|
| **Provisioning** | `ProvisioningService`: create A record (proxied) 9bal SSL/Nginx; rollback delete record. |
| **SSL** | `SslProvisioningService`: Certbot b `--dns-cloudflare` (CLOUDFLARE_DNS_TOKEN). |
| **Domain Center** | DNS inventory modal: zone_id, cf_record_id, records by hostname. |
| **Runbooks (Control Center)** | `domain_cf_purge_cache_host`, `domain_cf_purge_cache_zone`, `domain_cf_delete_dns`. |
| **Domain Center UI** | Actions: Cloudflare purge (host), Cloudflare purge (zone). |
| **Cache (admin)** | `CacheController::purgeTenant` — purge by domain hostname. |
| **Domains table** | `cf_zone_id`, `cf_record_id` per domain. |

### 4) Doc

- **`documentation/CLOUDFLARE-RULES.md`** — ruleset recommended (cache, bypass API, security, DNS/SSL).

---

## Plan (ila bghiti t-zid 7aja)

- **Daba:** token + zone_id (aw per-domain cf_zone_id), DNS create/delete, purge cache, SSL via Certbot DNS-01. Doc CLOUDFLARE-RULES.md.
- **Ila bghiti t-zid:**
  - **Multi-zone:** daba 3adna per-domain `cf_zone_id`; tansa2od t-assign zone men Domain Center ila ma 3andekch zone par défaut.
  - **WAF / Firewall rules:** men Cloudflare dashboard (CLOUDFLARE-RULES.md kayn security); API WAF rules ila bghiti f platform — 7aja jdida (calls API Cloudflare WAF).
  - **Analytics / Logpush:** men Cloudflare dashboard aw API; ila bghiti widgets f platform — 7aja jdida (API analytics aw webhooks).
  - **Health checks / Uptime:** l-platform 3andha déjà monitoring (uptime checks); tansa2od t-connect Cloudflare health checks b had l-module.

**Conclusion:** Cloudflare **insert 3adna** (DNS, SSL, purge, Domain Center, runbooks). Plan = config token/zone_id, t-follow CLOUDFLARE-RULES.md; ila bghiti WAF/analytics f UI = features jdad.

---

## Ach radi yban logs (wach khdam, koulchi cool)

| Kayn fayn | Ach kay yban |
|-----------|---------------|
| **Control Center** (`/platform/control`) | Ila dert runbook (Cloudflare purge, delete DNS, etc.): f nafs l-page kay tla3 **"Last action"** + **output** (success/failed w text). Link **"View audit logs"** l Audit Logs. |
| **Audit Logs** (`/platform/audit_logs`) | Kol runbook yt-recordi f **audit_logs**: Date, User, Action = `runbook`, Description = label + action id (e.g. "Domain: Cloudflare purge (host) (domain_cf_purge_cache_host)"). Bach t-chouf wach Cloudflare (aw ay runbook) khdam. |
| **Laravel log** (`storage/logs/laravel.log`) | Cloudflare API errors (e.g. cache level update failed, page rule failed) — `Log::error` f `CloudflareService`. Ila 7aja failit, 9ra had l-file. |
| **Domain / Tenant** | Ila provisioning failit: `domain.last_error` aw `tenant.instance_last_error` yban f UI (Domain Center, tenant page). SSL failit: certificate last_error. |

**Résumé:** Logs platform side = **Control Center** (output direct ba3d runbook), **Audit Logs** (kol actions), **storage/logs/laravel.log** (errors). Ila koulchi cool, t-l9ahom f had l-3 places.
