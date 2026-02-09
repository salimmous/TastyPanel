import { getHomeData } from '../lib/api';

export const revalidate = 60;

export default async function HomePage() {
  const data = await getHomeData().catch(() => ({ articles: [], recipes: [] }));

  return (
    <div className="max-w-5xl mx-auto p-6 space-y-10">
      <section>
        <h1 className="text-3xl font-bold mb-4">Latest Articles</h1>
        <div className="grid md:grid-cols-3 gap-4">
          {(data.articles || []).map((a) => (
            <a key={a.slug} href={`/articles/${a.slug}`} className="border rounded-xl p-4 hover:shadow">
              <p className="text-xs text-gray-500">{a.created_at}</p>
              <h3 className="font-semibold text-lg">{a.title}</h3>
              <p className="text-sm text-gray-600 line-clamp-3">{a.description}</p>
            </a>
          ))}
        </div>
      </section>

      <section>
        <h2 className="text-2xl font-semibold mb-4">Featured Recipes</h2>
        <div className="grid md:grid-cols-3 gap-4">
          {(data.recipes || []).map((r) => (
            <a key={r.slug} href={`/recipes/${r.slug}`} className="border rounded-xl p-4 hover:shadow">
              <p className="text-xs text-gray-500">{r.category?.name || 'Recipe'}</p>
              <h3 className="font-semibold text-lg">{r.title}</h3>
              <p className="text-sm text-gray-600 line-clamp-3">{r.description}</p>
            </a>
          ))}
        </div>
      </section>
    </div>
  );
}
