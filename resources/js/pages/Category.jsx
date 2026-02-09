import { useParams, Link } from 'react-router-dom';
import { categories, recipes } from '../data';
import SectionHeader from '../components/SectionHeader';
import RecipeCard from '../components/RecipeCard';
import { ChevronDown, SlidersHorizontal } from 'lucide-react';

export default function Category() {
    const { id } = useParams();
    const category = categories.find(c => c.id === id);
    const categoryRecipes = recipes.filter(r => r.categoryId === id);

    if (!category) {
        return <div className="text-center py-20 flex flex-col items-center justify-center min-h-[50vh]">
            <h2 className="text-4xl font-serif font-bold text-gray-300 mb-4">404</h2>
            <p className="text-text-secondary">Category not found</p>
            <Link to="/" className="mt-6 px-6 py-2 bg-black text-white rounded-lg hover:bg-brand transition-colors">Return Home</Link>
        </div>;
    }

    return (
        <div className="container mx-auto px-4 md:px-8 pb-20">
            {/* Category Hero */}
            <div className="relative py-20 md:py-24 mb-12 -mx-4 md:-mx-8 px-4 md:px-8 bg-gray-50 overflow-hidden">
                {/* Background Decoration */}
                <div className="absolute top-0 right-0 w-64 h-64 bg-brand/5 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
                <div className="absolute bottom-0 left-0 w-48 h-48 bg-yellow-500/5 rounded-full blur-2xl transform -translate-x-1/2 translate-y-1/2"></div>

                <div className="max-w-4xl mx-auto text-center relative z-10">
                    <div className="flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-widest text-text-muted mb-6">
                        <Link to="/" className="hover:text-brand transition-colors">Home</Link>
                        <span className="text-brand">/</span>
                        <span>Collection</span>
                    </div>

                    <h1 className="text-5xl md:text-7xl font-serif font-bold text-text-main mb-6 tracking-tight relative inline-block">
                        {category.name}
                        <span className="absolute -bottom-2 md:-bottom-4 left-1/2 -translate-x-1/2 w-24 h-1.5 bg-brand rounded-full"></span>
                    </h1>

                    <p className="text-lg md:text-xl text-text-secondary leading-relaxed max-w-2xl mx-auto font-light mt-8">
                        {category.description}
                    </p>
                </div>
            </div>

            {/* Filters & Sort Bar */}
            <div className="flex flex-col md:flex-row justify-between items-center mb-12 pb-6 border-b border-gray-100 gap-4">
                <div className="text-text-secondary font-medium">
                    Showing <span className="font-bold text-text-main">{categoryRecipes.length}</span> recipes
                </div>

                <div className="flex items-center gap-4">
                    <button className="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-bold text-text-secondary hover:border-brand hover:text-brand transition-colors bg-white">
                        <SlidersHorizontal className="w-4 h-4" /> Filter
                    </button>
                    <div className="relative group">
                        <button className="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm font-bold text-text-secondary hover:border-brand hover:text-brand transition-colors bg-white">
                            Sort by: Newest <ChevronDown className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            {categoryRecipes.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 gap-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
                    {categoryRecipes.map((recipe, idx) => (
                        <RecipeCard key={recipe.id} recipe={recipe} />
                    ))}
                </div>
            ) : (
                <div className="text-center py-24 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                    <div className="text-6xl mb-4">🍳</div>
                    <h3 className="font-serif text-2xl font-bold text-text-main mb-2">No recipes yet</h3>
                    <p className="text-text-muted">We haven't added recipes to this category correctly yet.</p>
                </div>
            )}
        </div>
    );
}
