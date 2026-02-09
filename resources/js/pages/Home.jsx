import { Link } from 'react-router-dom';
import { recipes, categories, articles } from '../data';
import CategoryCard from '../components/CategoryCard';
import RecipeCard from '../components/RecipeCard';
import ArticleCard from '../components/ArticleCard';
import SectionHeader from '../components/SectionHeader';
import TrendingCard from '../components/TrendingCard';
import Pagination from '../components/Pagination';
import { ArrowRight, ChefHat, Coffee, Globe, ArrowDown } from 'lucide-react';

export default function Home() {
    const latestRecipes = recipes.slice(0, 8);
    const trendingRecipes = recipes.slice(0, 6);

    const scrollToContent = () => {
        document.getElementById('content')?.scrollIntoView({ behavior: 'smooth' });
    };

    return (
        <div className="bg-white pb-20">

            {/* Immersive Hero Section - "Max" Design */}
            <div className="relative h-[90vh] w-full overflow-hidden mb-20">
                <div className="absolute inset-0">
                    <img
                        src="https://images.unsplash.com/photo-1556910103-1c02745a30bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=2400&q=80"
                        alt="Kitchen Table"
                        className="w-full h-full object-cover"
                    />
                    <div className="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent md:to-black/30" />
                </div>

                <div className="absolute inset-0 flex items-center">
                    <div className="container mx-auto px-4 md:px-8">
                        <div className="max-w-3xl animate-in fade-in slide-in-from-bottom-8 duration-1000">
                            <div className="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-md border border-white/20 text-white text-xs font-bold uppercase tracking-[0.2em] rounded-full mb-8">
                                <ChefHat className="w-4 h-4" /> Est. 2025
                            </div>
                            <h1 className="text-6xl md:text-8xl lg:text-9xl font-serif font-bold text-white mb-8 leading-none shadow-sm">
                                Taste the <br /> <span className="text-transparent bg-clip-text bg-gradient-to-r from-brand-light to-white">Extraordinary.</span>
                            </h1>
                            <p className="text-xl md:text-2xl text-white/90 font-light max-w-2xl mb-12 leading-relaxed border-l-4 border-brand pl-8">
                                Weekly recipes that transform simple ingredients into unforgettable memories. From our kitchen to yours.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-6">
                                <button onClick={scrollToContent} className="px-10 py-5 bg-brand text-white font-bold uppercase tracking-widest rounded-full hover:bg-white hover:text-black transition-all shadow-2xl hover:shadow-white/20 flex items-center justify-center gap-3">
                                    Start Cooking <ArrowDown className="w-5 h-5" />
                                </button>
                                <Link to="/about" className="px-10 py-5 bg-white/10 backdrop-blur-md border border-white/30 text-white font-bold uppercase tracking-widest rounded-full hover:bg-white hover:text-black transition-all flex items-center justify-center gap-3">
                                    Read Our Story <ArrowRight className="w-5 h-5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="container mx-auto px-4 md:px-8" id="content">

                {/* Categories Slider */}
                <div className="mb-24">
                    <SectionHeader title="Curated Collections" />
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        {categories.map(cat => (
                            <CategoryCard key={cat.id} category={cat} />
                        ))}
                    </div>
                </div>

                {/* Main Content Split */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-16 mb-24">

                    {/* Main Feed */}
                    <div className="lg:col-span-8">
                        <div className="flex items-end justify-between mb-12 border-b border-gray-100 pb-6">
                            <h2 className="text-4xl font-serif font-bold text-text-main">Latest Recipes</h2>
                            <Link to="#" className="hidden md:flex items-center gap-2 text-sm font-bold text-brand hover:text-black transition-colors uppercase tracking-widest">
                                View Archive <ArrowRight className="w-4 h-4" />
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-12">
                            {latestRecipes.map(recipe => (
                                <RecipeCard key={recipe.id} recipe={recipe} />
                            ))}
                        </div>

                        <div className="mt-16">
                            <Pagination />
                        </div>
                    </div>

                    {/* Premium Sidebar */}
                    <aside className="lg:col-span-4 space-y-12 h-fit lg:sticky lg:top-24">
                        {/* About Widget */}
                        <div className="bg-white p-10 rounded-[2.5rem] border border-gray-100 shadow-2xl shadow-gray-100/50 text-center relative overflow-hidden group">
                            <div className="absolute top-0 left-0 w-full h-2 bg-brand transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500" />
                            <div className="w-28 h-28 mx-auto -mt-4 mb-6 rounded-full p-1 border-2 border-dashed border-gray-200">
                                <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80" alt="Author" className="w-full h-full rounded-full object-cover" />
                            </div>
                            <h3 className="font-serif text-3xl font-bold text-text-main mb-2">Clare Doe</h3>
                            <p className="text-[10px] text-brand font-bold uppercase tracking-[0.2em] mb-6">Editor-in-Chief</p>
                            <p className="text-text-secondary mb-8 leading-relaxed font-light">
                                "Food is the universal language of love. Join me as we explore flavors from around the world."
                            </p>
                            <Link to="/about" className="text-xs font-bold uppercase tracking-widest border-b-2 border-black pb-1 hover:text-brand hover:border-brand transition-colors"> More About Me</Link>
                        </div>

                        {/* Newsletter Widget - Dark */}
                        <div className="bg-[#1a1a1a] p-10 rounded-[2.5rem] text-white text-center relative overflow-hidden">
                            <div className="absolute -top-10 -right-10 w-40 h-40 bg-brand rounded-full blur-[80px] opacity-50" />
                            <h3 className="font-serif text-3xl font-bold mb-4 relative z-10">Join the Club</h3>
                            <p className="text-white/60 mb-8 text-sm font-medium tracking-wide relative z-10">Get exclusive recipes & tips delivered weekly.</p>

                            <div className="space-y-4 relative z-10">
                                <input
                                    type="email"
                                    placeholder="Your email address"
                                    className="w-full px-6 py-4 rounded-xl text-white placeholder-white/30 bg-white/5 border border-white/10 focus:outline-none focus:border-brand focus:bg-white/10 transition-all"
                                />
                                <button className="w-full py-4 bg-brand hover:bg-brand-dark text-white transition-all duration-300 rounded-xl font-bold uppercase text-xs tracking-[0.2em] shadow-lg shadow-brand/20">
                                    Subscribe
                                </button>
                            </div>
                        </div>

                        {/* Trending Tags */}
                        <div className="bg-[#FAF9F6] p-10 rounded-[2.5rem] border border-dashed border-gray-300">
                            <h3 className="font-serif text-2xl font-bold mb-6 text-center">Popular Tags</h3>
                            <div className="flex flex-wrap justify-center gap-3">
                                {['Breakfast', 'Vegan', 'Dessert', 'Quick', 'Gluten Free', 'Dinner', 'Italian', 'Healthy'].map(tag => (
                                    <span key={tag} className="px-4 py-2 bg-white border border-gray-200 rounded-full text-xs font-bold uppercase tracking-wider text-text-secondary hover:bg-black hover:text-white hover:border-black cursor-pointer transition-all">
                                        {tag}
                                    </span>
                                ))}
                            </div>
                        </div>
                    </aside>
                </div>

                {/* Articles Section */}
                <div className="mb-24">
                    <SectionHeader title="Deep Dives & Stories" />
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {articles.map(article => (
                            <ArticleCard key={article.id} article={article} />
                        ))}
                    </div>
                </div>

                {/* Trending Carousel */}
                <div className="bg-black text-white -mx-4 md:-mx-8 px-4 md:px-8 py-20 mb-[-5rem] relative overflow-hidden">
                    <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-brand rounded-full blur-[150px] opacity-20 pointer-events-none" />
                    <div className="container mx-auto relative z-10">
                        <h2 className="text-4xl md:text-5xl font-serif font-bold mb-12 text-center">Trending Now</h2>

                        <div className="flex gap-8 overflow-x-auto pb-12 snap-x hide-scrollbar scroll-smooth">
                            {trendingRecipes.map(recipe => (
                                <div key={recipe.id} className="w-[300px] md:w-[350px] flex-shrink-0 snap-center">
                                    <TrendingCard recipe={recipe} />
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
