import { X, Search as SearchIcon, Clock } from 'lucide-react';
import { useState, useEffect } from 'react';
import { recipes } from '../data';
import { Link } from 'react-router-dom';

export default function SearchModal({ isOpen, onClose }) {
    const [searchTerm, setSearchTerm] = useState('');
    const [results, setResults] = useState([]);

    useEffect(() => {
        if (searchTerm.trim() === '') {
            setResults([]);
            return;
        }
        const filtered = recipes.filter(r =>
            r.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            r.ingredients.some(i => i.toLowerCase().includes(searchTerm.toLowerCase()))
        );
        setResults(filtered);
    }, [searchTerm]);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-[100] flex items-start justify-center pt-20 px-4">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-white/90 backdrop-blur-sm transition-opacity"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
                <div className="p-4 border-b border-gray-100 flex items-center gap-3">
                    <SearchIcon className="w-5 h-5 text-gray-400" />
                    <input
                        type="text"
                        placeholder="Search recipes, ingredients..."
                        className="flex-1 text-lg outline-none placeholder:text-gray-300 font-serif"
                        autoFocus
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                    <button onClick={onClose} className="p-1 hover:bg-gray-100 rounded-full transition-colors">
                        <X className="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                <div className="max-h-[60vh] overflow-y-auto p-2">
                    {results.length > 0 ? (
                        <div className="grid gap-2">
                            {results.map(recipe => (
                                <Link
                                    key={recipe.id}
                                    to={`/recipe/${recipe.id}`}
                                    onClick={onClose}
                                    className="flex items-center gap-4 p-2 hover:bg-gray-50 rounded-xl transition-colors group"
                                >
                                    <img src={recipe.image} alt={recipe.title} className="w-16 h-16 rounded-lg object-cover" />
                                    <div>
                                        <h4 className="font-bold text-text-main group-hover:text-brand transition-colors">{recipe.title}</h4>
                                        <div className="flex items-center gap-3 text-xs text-text-muted mt-1">
                                            <span className="flex items-center gap-1"><Clock className="w-3 h-3" /> {recipe.prepTime}</span>
                                            <span className="capitalize">{recipe.categoryId.replace('-', ' ')}</span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    ) : searchTerm ? (
                        <div className="p-8 text-center text-text-muted">
                            No recipes found for "{searchTerm}"
                        </div>
                    ) : (
                        <div className="p-8 text-center text-text-muted text-sm">
                            Type to start searching...
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
