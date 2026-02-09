import { Link } from 'react-router-dom';

export default function RecipeCard({ recipe }) {
    return (
        <div className="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-shadow border border-gray-100 flex flex-col h-full group">
            <div className="relative aspect-[4/3] overflow-hidden">
                <img
                    src={recipe.image}
                    alt={recipe.title}
                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
                <div className="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-2 py-1 rounded text-xs font-bold text-brand uppercase tracking-wider shadow-sm">
                    {recipe.categoryId.replace('-', ' ')}
                </div>
            </div>

            <div className="p-5 flex flex-col flex-grow">
                <h3 className="font-sans font-bold text-lg leading-tight mb-2 text-text-main group-hover:text-brand transition-colors">
                    <Link to={`/recipe/${recipe.id}`}>
                        {recipe.title}
                    </Link>
                </h3>

                <p className="text-sm text-text-muted mb-4 line-clamp-3 leading-relaxed flex-grow">
                    {recipe.description}
                </p>

                <div className="pt-4 border-t border-gray-100 flex items-center justify-between text-xs font-medium text-text-secondary mt-auto">
                    <div className="flex gap-3">
                        <span>Make 'em</span>
                    </div>
                    <span className="capitalize text-brand font-bold">{recipe.categoryId.split('-').join(' ')}</span>
                </div>
            </div>
        </div>
    );
}
