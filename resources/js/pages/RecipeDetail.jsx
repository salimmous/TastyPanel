import { useParams, Link } from 'react-router-dom';
import { recipes } from '../data';
import { Clock, Users, ArrowLeft, Printer, Share2, Heart, CheckCircle2, Circle, Flame, ChefHat, Utensils, ArrowDown } from 'lucide-react';
import SectionHeader from '../components/SectionHeader';
import RecipeCard from '../components/RecipeCard';
import Reviews from '../components/Reviews';
import { useState } from 'react';

export default function RecipeDetail() {
    const { id } = useParams();
    const [checkedIngredients, setCheckedIngredients] = useState({});
    const recipe = recipes.find(r => r.id === id);

    // Get similar recipes
    const similarRecipes = recipes
        .filter(r => r.categoryId === recipe?.categoryId && r.id !== recipe?.id)
        .slice(0, 3);

    if (!recipe) return <div className="min-h-screen flex items-center justify-center text-xl font-serif">Recipe not found</div>;

    const toggleIngredient = (index) => {
        setCheckedIngredients(prev => ({
            ...prev,
            [index]: !prev[index]
        }));
    };

    const scrollToRecipe = () => {
        document.getElementById('instructions')?.scrollIntoView({ behavior: 'smooth' });
    };

    return (
        <div className="bg-white min-h-screen pb-20">
            {/* Immersive Hero Section */}
            <div className="relative h-[85vh] w-full overflow-hidden">
                <div className="absolute inset-0">
                    <img src={recipe.image} alt={recipe.title} className="w-full h-full object-cover" />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-black/30" />
                </div>

                <div className="absolute top-8 left-0 right-0 z-20 px-4 md:px-8">
                    <Link to="/" className="inline-flex items-center text-sm font-bold text-white/90 hover:text-white hover:bg-white/10 px-4 py-2 rounded-full transition-all backdrop-blur-sm">
                        <ArrowLeft className="w-4 h-4 mr-2" /> Back to Collection
                    </Link>
                </div>

                <div className="absolute bottom-0 left-0 w-full p-6 md:p-12 lg:p-20 text-white z-10">
                    <div className="max-w-5xl mx-auto">
                        <div className="inline-flex items-center gap-2 px-3 py-1 bg-brand text-white text-[10px] font-bold uppercase tracking-[0.2em] rounded-md mb-6 shadow-lg">
                            {recipe.categoryId.split('-').join(' ')}
                        </div>

                        <h1 className="text-5xl md:text-7xl lg:text-8xl font-serif font-bold mb-8 leading-none tracking-tight drop-shadow-xl">
                            {recipe.title}
                        </h1>

                        <p className="text-lg md:text-2xl text-white/90 font-light max-w-2xl mb-12 leading-relaxed drop-shadow-md border-l-4 border-brand pl-6">
                            {recipe.description}
                        </p>

                        <div className="flex flex-col md:flex-row items-start md:items-center gap-8">
                            <div className="flex flex-wrap items-center gap-4 md:gap-8 backdrop-blur-md bg-white/10 border border-white/20 p-6 rounded-2xl w-fit">
                                <div className="flex items-center gap-3 pr-8 border-r border-white/20 last:border-0">
                                    <Clock className="w-6 h-6 text-brand-light" />
                                    <div>
                                        <div className="text-[10px] text-white/70 uppercase tracking-wider font-bold">Prep</div>
                                        <div className="text-lg font-bold">{recipe.prepTime}</div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3 pr-8 border-r border-white/20 last:border-0">
                                    <Flame className="w-6 h-6 text-brand-light" />
                                    <div>
                                        <div className="text-[10px] text-white/70 uppercase tracking-wider font-bold">Cook</div>
                                        <div className="text-lg font-bold">{recipe.cookTime}</div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Users className="w-6 h-6 text-brand-light" />
                                    <div>
                                        <div className="text-[10px] text-white/70 uppercase tracking-wider font-bold">Serves</div>
                                        <div className="text-lg font-bold">{recipe.servings} pp</div>
                                    </div>
                                </div>
                            </div>

                            <button
                                onClick={scrollToRecipe}
                                className="px-8 py-4 bg-white text-black font-bold uppercase tracking-widest rounded-full hover:bg-brand hover:text-white transition-all shadow-xl flex items-center gap-2"
                            >
                                Jump to Recipe <ArrowDown className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Story / Intro Section - Full Width for Mobile/Desktop Flow */}
            <div className="max-w-4xl mx-auto px-4 md:px-8 py-12 md:py-16">
                <div className="prose prose-lg text-text-secondary max-w-none font-serif">
                    <p className="lead text-xl md:text-2xl text-text-main italic mb-8">
                        "This isn't just a recipe; it's a hug on a plate. The combination of sweet apples, creamy brie, and savory spinach creates a flavor profile that feels fancy but is incredibly easy to achieve."
                    </p>

                    <h3 className="text-2xl md:text-3xl font-bold text-text-main mt-12 mb-6">The Story Behind This Dish</h3>
                    <p className="mb-6 leading-relaxed">
                        I first discovered the magic of combining fruit with poultry during a trip to Normandy, France. There, apples aren't just for dessert—they are a staple in savory cooking. I remember walking past a small bistro that smelled of butter, caramelized apples, and roasting meat. That scent has stuck with me for years.
                    </p>
                    <p className="mb-6 leading-relaxed">
                        When I came home, I wanted to recreate that feeling but with a weeknight-friendly twist. Stuffed chicken breast is often thought of as "restaurant food" or something you only make for a dinner party. But the truth is, with the right technique, it's a 30-minute meal that upgrades your Tuesday night dinner rotation instantly.
                    </p>

                    {/* Ad Placeholder */}
                    <div className="my-8 md:my-12 p-8 bg-gray-50 border border-dashed border-gray-300 rounded-xl text-center text-text-muted uppercase tracking-widest text-xs">
                        Ad Space / Banner Area
                    </div>

                    <h3 className="text-2xl md:text-3xl font-bold text-text-main mt-12 mb-6">Why You'll Love This Recipe</h3>
                    <ul className="list-disc pl-6 space-y-4 mb-12 marker:text-brand">
                        <li><strong>Quick & Easy:</strong> Looks impressive but comes together in under 45 minutes.</li>
                        <li><strong>Flavor Bomb:</strong> Sweet, savory, creamy, and herbaceous all in one bite.</li>
                        <li><strong>Low Carb / Keto Friendly:</strong> Just swap the apples for more spinach or mushrooms if you're strict keto.</li>
                        <li><strong>Meal Prep Gold:</strong> Reheats surprisingly well for lunch the next day.</li>
                    </ul>

                    <h3 className="text-2xl md:text-3xl font-bold text-text-main mt-12 mb-6">Expert Tips for Success</h3>
                    <div className="bg-[#FAF9F6] p-6 md:p-8 rounded-2xl border-l-4 border-brand mb-12">
                        <h4 className="font-bold text-lg text-text-main mb-2">1. Don't slice all the way through</h4>
                        <p className="mb-4 text-base">When butterflying the chicken, be careful to leave a "hinge" so the filling stays inside.</p>

                        <h4 className="font-bold text-lg text-text-main mb-2">2. Use toothpicks</h4>
                        <p className="mb-4 text-base">Secure the open edges with toothpicks to prevent the cheese from leaking out too much during the sear.</p>

                        <h4 className="font-bold text-lg text-text-main mb-2">3. Room temperature cheese</h4>
                        <p className="text-base">Let your brie sit out for 20 minutes before slicing; it will melt more evenly.</p>
                    </div>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 md:px-8 -mt-8 relative z-20">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-12">

                    {/* Left Sidebar (Ingredients) - Order 1 on Mobile */}
                    <div className="lg:col-span-4 lg:sticky lg:top-24 h-fit space-y-8">
                        <div className="bg-white rounded-3xl p-6 md:p-8 shadow-2xl shadow-gray-200/50 border border-gray-100">
                            <div className="flex items-center justify-between mb-8">
                                <h3 className="font-serif text-2xl md:text-3xl font-bold flex items-center gap-3">
                                    Ingredients
                                </h3>
                                <span className="text-sm font-bold text-text-muted bg-gray-100 px-3 py-1 rounded-full">{recipe.ingredients.length} Items</span>
                            </div>

                            <div className="space-y-4">
                                {recipe.ingredients.map((ing, i) => (
                                    <label
                                        key={i}
                                        className={`flex items-start gap-4 p-4 rounded-xl transition-all cursor-pointer border ${checkedIngredients[i] ? 'bg-gray-50 border-transparent text-gray-400' : 'bg-white border-gray-100 hover:border-brand shadow-sm hover:shadow-md'}`}
                                    >
                                        <input
                                            type="checkbox"
                                            className="hidden"
                                            checked={!!checkedIngredients[i]}
                                            onChange={() => toggleIngredient(i)}
                                        />
                                        <div className={`mt-0.5 transition-colors duration-300 ${checkedIngredients[i] ? 'text-brand' : 'text-gray-300'}`}>
                                            {checkedIngredients[i] ? <CheckCircle2 className="w-5 h-5 fill-brand/10" /> : <Circle className="w-5 h-5" />}
                                        </div>
                                        <span className={`text-base font-medium leading-relaxed ${checkedIngredients[i] ? 'line-through decoration-brand/30' : 'text-text-main'}`}>
                                            {ing}
                                        </span>
                                    </label>
                                ))}
                            </div>

                            <div className="mt-8 pt-8 border-t border-dashed border-gray-200 flex gap-4">
                                <button className="flex-1 flex items-center justify-center gap-2 py-3 bg-gray-50 hover:bg-brand hover:text-white rounded-xl text-sm font-bold uppercase tracking-wider transition-colors">
                                    <Printer className="w-4 h-4" /> Print
                                </button>
                                <button className="flex-1 flex items-center justify-center gap-2 py-3 bg-gray-50 hover:bg-brand hover:text-white rounded-xl text-sm font-bold uppercase tracking-wider transition-colors">
                                    <Share2 className="w-4 h-4" /> Share
                                </button>
                            </div>
                        </div>

                        {/* Nutrition Mini-Card */}
                        <div className="bg-[#1a1a1a] text-white rounded-3xl p-8 shadow-xl">
                            <h4 className="font-serif text-xl font-bold mb-6 flex items-center gap-2">
                                <div className="p-1 bg-brand rounded-md"><Utensils className="w-4 h-4 text-white" /></div>
                                Nutrition Facts
                            </h4>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="bg-white/5 p-4 rounded-2xl text-center backdrop-blur-sm">
                                    <div className="text-2xl font-bold mb-1">{recipe.nutrition?.calories || '320'}</div>
                                    <div className="text-[10px] uppercase tracking-widest text-white/50">Calories</div>
                                </div>
                                <div className="bg-white/5 p-4 rounded-2xl text-center backdrop-blur-sm">
                                    <div className="text-2xl font-bold mb-1">{recipe.nutrition?.protein || '24g'}</div>
                                    <div className="text-[10px] uppercase tracking-widest text-white/50">Protein</div>
                                </div>
                                <div className="bg-white/5 p-4 rounded-2xl text-center backdrop-blur-sm">
                                    <div className="text-2xl font-bold mb-1">{recipe.nutrition?.fat || '12g'}</div>
                                    <div className="text-[10px] uppercase tracking-widest text-white/50">Fat</div>
                                </div>
                                <div className="bg-white/5 p-4 rounded-2xl text-center backdrop-blur-sm">
                                    <div className="text-2xl font-bold mb-1">{recipe.nutrition?.carbs || '4g'}</div>
                                    <div className="text-[10px] uppercase tracking-widest text-white/50">Carbs</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Right Content (Instructions) */}
                    <div className="lg:col-span-8 pt-12 lg:pt-0">
                        <div className="bg-white pb-12" id="instructions">
                            <div className="flex items-center gap-4 mb-8 md:mb-12">
                                <span className="flex items-center justify-center w-12 h-12 bg-black text-white rounded-full font-serif text-xl font-bold">1</span>
                                <h2 className="text-3xl md:text-4xl font-serif font-bold">Instructions</h2>
                            </div>

                            <div className="space-y-12 pl-4 md:pl-6 border-l-2 border-gray-100 ml-2 md:ml-6">
                                {recipe.instructions.map((step, i) => (
                                    <div key={i} className="relative pl-6 md:pl-8 group">
                                        <div className="absolute -left-[25px] md:-left-[33px] top-0 w-4 h-4 rounded-full bg-white border-4 border-gray-200 group-hover:border-brand transition-colors" />
                                        <h4 className="text-lg md:text-xl font-bold text-text-main mb-3 group-hover:text-brand transition-colors">Step {i + 1}</h4>
                                        <p className="text-lg md:text-xl text-text-secondary leading-relaxed font-light">{step}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Author Box Large */}
                        <div className="mt-16 bg-[#F8F5F2] rounded-[2rem] md:rounded-[3rem] p-8 md:p-16 text-center border border-[#E8E1D9] relative overflow-hidden">
                            <div className="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-transparent via-brand to-transparent opacity-20" />
                            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" alt="Chef" className="w-24 h-24 md:w-32 md:h-32 mx-auto rounded-full object-cover shadow-xl border-4 border-white mb-8" />

                            <h3 className="text-2xl md:text-4xl font-serif font-bold text-text-main mb-4">Made with love by Clare</h3>
                            <p className="text-lg md:text-xl text-text-secondary font-light max-w-2xl mx-auto mb-10 leading-relaxed">
                                "I hope this recipe brings a little bit of joy to your kitchen. Don't forget to take a picture and tag me on Instagram!"
                            </p>

                            <button className="px-8 py-4 md:px-10 md:py-5 bg-black text-white text-xs md:text-sm font-bold uppercase tracking-[0.2em] rounded-full hover:bg-brand transition-all shadow-xl hover:shadow-2xl hover:-translate-y-1">
                                More from Clare
                            </button>
                        </div>

                        {/* Similar Recipes */}
                        {similarRecipes.length > 0 && (
                            <div className="mt-24">
                                <SectionHeader title="More Like This" />
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    {similarRecipes.map(r => (
                                        <RecipeCard key={r.id} recipe={r} />
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="mt-24">
                            <Reviews recipeTitle={recipe.title} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
