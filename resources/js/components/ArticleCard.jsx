import { Link } from 'react-router-dom';

export default function ArticleCard({ article }) {
    return (
        <div className="flex flex-col h-full group bg-white rounded-2xl p-4 border border-transparent hover:border-gray-100 hover:shadow-xl transition-all duration-300">
            <div className="rounded-xl overflow-hidden mb-5 shadow-sm aspect-[3/2] relative">
                <div className="absolute top-3 left-3 bg-white/90 backdrop-blur px-3 py-1 text-[10px] font-bold tracking-widest uppercase rounded-sm z-10 text-text-main shadow-sm">
                    Read
                </div>
                <img
                    src={article.image}
                    alt={article.title}
                    className="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
                />
            </div>
            <div className="flex flex-col flex-grow text-center px-2">
                <h3 className="font-serif font-bold text-xl mb-3 text-text-main group-hover:text-brand transition-colors leading-snug">
                    <Link to="#">{article.title}</Link>
                </h3>
                <div className="w-8 h-0.5 bg-gray-200 mx-auto mb-3 group-hover:bg-brand transition-colors"></div>
                <p className="text-sm text-text-muted leading-relaxed line-clamp-3 font-light mb-4 text-secondary">
                    {article.description}
                </p>
                <div className="mt-auto pt-2">
                    <span className="text-xs font-bold text-brand uppercase tracking-wider group-hover:underline decoration-2 underline-offset-4">Read Article</span>
                </div>
            </div>
        </div>
    );
}
