import { ApiError } from './errors';

const apiBase = process.env.PLATFORM_API_BASE || 'http://localhost:8000/api';
const tenantHost = process.env.TENANT_HOST || 'localhost';
const tenantEnv = process.env.TENANT_ENV || 'production';
const partnerKey = process.env.PARTNER_API_KEY || null;

async function fetchJson(path) {
  const url = `${apiBase}${path}`;
  const res = await fetch(url, {
    headers: {
      'X-Tenant-Host': tenantHost,
      'X-Environment': tenantEnv,
      ...(partnerKey ? { 'X-Api-Key': partnerKey } : {}),
    },
    next: { revalidate: 60 },
  });
  if (!res.ok) {
    let data;
    try {
      data = await res.json();
    } catch (e) {
      data = { message: res.statusText };
    }
    throw new ApiError(url, res.status, data);
  }
  return res.json();
}

export async function getHomeData() {
  const [articles, recipes] = await Promise.all([
    fetchJson('/articles?per_page=6'),
    fetchJson('/recipes?per_page=6'),
  ]);
  return { articles: articles?.data || [], recipes: recipes?.data || [] };
}

export async function getArticle(slug) {
  return fetchJson(`/articles/${slug}`);
}

export async function getRecipe(slug) {
  return fetchJson(`/recipes/${slug}`);
}

export async function getArticleSlugs(limit = 50) {
  const data = await fetchJson(`/articles?per_page=${limit}`);
  return (data?.data || []).map((a) => a.slug);
}

export async function getRecipeSlugs(limit = 50) {
  const data = await fetchJson(`/recipes?per_page=${limit}`);
  return (data?.data || []).map((r) => r.slug);
}
