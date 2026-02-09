import { ChevronRight } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function TrendingCard({ recipe }) {
    return (
        <div className="flex flex-col group min-w-[200px] relative">
            <div className="relative rounded-2xl overflow-hidden mb-4 aspect-[4/5] shadow-md">
                <div className="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-colors z-10" />
                <img
                    src={recipe.image}
                    alt={recipe.title}
                    className="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-110"
                />
                <div className="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent z-20">
                    <span className="text-[10px] font-bold text-white/90 uppercase tracking-widest bg-brand/80 px-2 py-0.5 rounded-sm inline-block mb-2">Trending</span>
                </div>
            </div>

            <h4 className="font-serif font-bold text-lg text-text-main group-hover:text-brand transition-colors leading-tight px-1 mb-2">
                <Link to={`/recipe/${recipe.id}`}>
                    {recipe.title}
                </Link>
            </h4>
            <div className="flex items-center text-xs font-bold text-brand uppercase tracking-wider px-1 opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0">
                View Recipe <ChevronRight className="w-3 h-3 ml-1" />
            </div>
        </div>
    );
}
