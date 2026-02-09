# Next.js Frontend (Tenant Sites)

Location: `frontend/`

Setup:
```
cd frontend
# one-click: TENANT_HOST=yourdomain.com PLATFORM_API_BASE=https://platform.example.com/api ./install.sh
# dev:      npm run dev
# prod:     npm run build && npm start
```

Env:
- `PLATFORM_API_BASE` = `https://platform.example.com/api`
- `TENANT_HOST` = tenant primary domain (used as Host header so Laravel resolves tenant)
- `TENANT_ENV` = `production|staging`
- `PARTNER_API_KEY` = optional if APIs get locked later

Pages (app router, SSG+ISR):
- `/` uses `getHomeData` (articles/recipes lists)
- `/articles/[slug]` and `/recipes/[slug]` prebuild latest 50 and revalidate every 5 min.

Revalidate hook (optional):
- Add a server route to hit `revalidateTag` when webhooks fire, or keep current 5 min ISR.

Deploy:
- Any Node host/Vercel. Needs Node 18+, `npm run build`, `npm start`.
