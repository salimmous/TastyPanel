import { getArticle, getArticleSlugs } from '../../../lib/api';

export async function generateStaticParams() {
  const slugs = await getArticleSlugs();
  return slugs.map((slug) => ({ slug }));
}

export const revalidate = 300;

export default async function ArticlePage({ params }) {
  const article = await getArticle(params.slug).catch(() => null);
  if (!article) {
    return <div className="p-6">Not found</div>;
  }
  return (
    <article className="max-w-3xl mx-auto p-6 space-y-4">
      <p className="text-sm text-gray-500">{article.created_at}</p>
      <h1 className="text-3xl font-bold">{article.title}</h1>
      <div className="text-gray-700 leading-relaxed whitespace-pre-line">
        {article.description}
      </div>
    </article>
  );
}
