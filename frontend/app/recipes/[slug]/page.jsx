import { getRecipe, getRecipeSlugs } from '../../../lib/api';

export async function generateStaticParams() {
  const slugs = await getRecipeSlugs();
  return slugs.map((slug) => ({ slug }));
}

export const revalidate = 300;

export default async function RecipePage({ params }) {
  const recipe = await getRecipe(params.slug).catch(() => null);
  if (!recipe) {
    return <div className="p-6">Not found</div>;
  }
  return (
    <article className="max-w-3xl mx-auto p-6 space-y-4">
      <p className="text-sm text-gray-500">{recipe.created_at}</p>
      <h1 className="text-3xl font-bold">{recipe.title}</h1>
      <p className="text-gray-600">{recipe.category?.name || 'Recipe'}</p>
      <div className="text-gray-700 leading-relaxed whitespace-pre-line">
        {recipe.description}
      </div>
    </article>
  );
}
