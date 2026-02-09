import { useState, useEffect } from 'react';
import api from '../services/api';
import CategoryCard from '../components/CategoryCard';
import RecipeCard from '../components/RecipeCard';

/**
 * Example component showing how to use the Laravel API
 * This demonstrates fetching data from the backend instead of using static data
 */
function ExampleApiUsage() {
    const [categories, setCategories] = useState([]);
    const [recipes, setRecipes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchData = async () => {
            try {
                setLoading(true);

                // Fetch categories and recipes from Laravel API
                const [categoriesData, recipesData] = await Promise.all([
                    api.getCategories(),
                    api.getRecipes()
                ]);

                setCategories(categoriesData);
                setRecipes(recipesData);
                setError(null);
            } catch (err) {
                console.error('Error fetching data:', err);
                setError('Failed to load data from API');
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto mb-4"></div>
                    <p className="text-gray-600">Loading...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="text-center">
                    <p className="text-red-600 mb-4">{error}</p>
                    <button
                        onClick={() => window.location.reload()}
                        className="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600"
                    >
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="container mx-auto px-4 py-8">
            {/* Categories Section */}
            <section className="mb-12">
                <h2 className="text-3xl font-bold mb-6">Categories</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {categories.map(category => (
                        <CategoryCard
                            key={category.id}
                            category={category}
                        />
                    ))}
                </div>
            </section>

            {/* Recipes Section */}
            <section>
                <h2 className="text-3xl font-bold mb-6">All Recipes</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {recipes.map(recipe => (
                        <RecipeCard
                            key={recipe.id}
                            recipe={recipe}
                        />
                    ))}
                </div>
            </section>
        </div>
    );
}

export default ExampleApiUsage;

/**
 * USAGE EXAMPLES:
 * 
 * 1. Fetch all categories:
 *    const categories = await api.getCategories();
 * 
 * 2. Fetch single category by slug:
 *    const category = await api.getCategory('evening-meals');
 * 
 * 3. Fetch all recipes:
 *    const recipes = await api.getRecipes();
 * 
 * 4. Fetch recipes by category:
 *    const recipes = await api.getRecipesByCategory('sweet-treats');
 * 
 * 5. Search recipes:
 *    const results = await api.searchRecipes('chicken');
 * 
 * 6. Fetch single recipe by slug:
 *    const recipe = await api.getRecipe('apple-brie-stuffed-chicken');
 * 
 * 7. Fetch all articles:
 *    const articles = await api.getArticles();
 */
