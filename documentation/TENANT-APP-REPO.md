# Tenant App Repo (Template)

Use this guide to create a dedicated tenant‑site repository that the platform clones for each new site. This repo can be your **theme + dashboard** (one patch) for every tenant — see **Workflow:** `documentation/TENANT-WORKFLOW.md`.

## 1) Export a clean tenant app

From the platform repo:

```bash
bash infrastructure/export-tenant-app.sh /var/www/tenant-app-template
```

This copies the code without `.git`, `node_modules`, `vendor`, `storage`, or `.env`.

## 2) Configure tenant mode defaults

Inside `/var/www/tenant-app-template/.env.example`, keep these defaults:

```
APP_MODE=tenant
TENANT_MODE=true
VITE_APP_MODE=tenant
```

You can also keep `TENANT_LOCK_ID` empty; the app will use the first tenant record.

## 3) Initialize a new git repo

```bash
cd /var/www/tenant-app-template
git init
git add .
git commit -m "tenant app template"
```

Push it to GitHub (or any git provider).

## 4) Point the platform to the tenant repo

Set in the control panel `.env`:

```
TENANT_APP_REPO=https://github.com/you/tenant-app-template.git
TENANT_APP_BRANCH=main
```

Now every new tenant will be cloned from this repo and run in single‑tenant mode.

## Workflow (theme + dashboard, install, admin access)

For the full order — create tenant → provision instance (install f backend) → admin access → setup site m9ad — see **`documentation/TENANT-WORKFLOW.md`**.
