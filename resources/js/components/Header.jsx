import { useState } from 'react';
import { Search, Apple, Menu, X } from 'lucide-react';
import { Link } from 'react-router-dom';
import SearchModal from './SearchModal';

export default function Header() {
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isSearchOpen, setIsSearchOpen] = useState(false);

    return (
        <>
            <header className="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm transition-all duration-300">
                <div className="container mx-auto px-4 md:px-8 py-4 flex items-center justify-between">
                    <Link to="/" className="flex items-center gap-2 group z-50 relative">
                        <div className="relative">
                            <Apple className="w-8 h-8 text-brand fill-brand/20 group-hover:scale-110 transition-transform duration-500 ease-out" strokeWidth={1.5} />
                            <div className="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                        </div>
                        <div className="flex flex-col">
                            <span className="font-serif text-2xl font-bold text-text-main leading-none">Recipes By Clare</span>
                            <span className="text-[10px] tracking-wider text-brand font-medium uppercase mt-0.5">Delicious Recipes Made Simple</span>
                        </div>
                    </Link>

                    {/* Desktop Nav */}
                    <nav className="hidden md:flex items-center gap-8 font-sans font-medium text-sm tracking-wide text-text-secondary">
                        <Link to="/" className="flex items-center gap-2 px-4 py-2 bg-brand-dark text-white rounded-full hover:shadow-lg hover:shadow-brand/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                            Categories
                        </Link>
                        <a href="#" className="hover:text-brand transition-colors relative after:content-[''] after:absolute after:w-full after:scale-x-0 after:h-0.5 after:bottom-0 after:left-0 after:bg-brand after:origin-bottom-right after:transition-transform after:duration-300 hover:after:scale-x-100 hover:after:origin-bottom-left">Explore</a>
                        <a href="#" className="hover:text-brand transition-colors relative after:content-[''] after:absolute after:w-full after:scale-x-0 after:h-0.5 after:bottom-0 after:left-0 after:bg-brand after:origin-bottom-right after:transition-transform after:duration-300 hover:after:scale-x-100 hover:after:origin-bottom-left">About</a>
                        <a href="#" className="hover:text-brand transition-colors relative after:content-[''] after:absolute after:w-full after:scale-x-0 after:h-0.5 after:bottom-0 after:left-0 after:bg-brand after:origin-bottom-right after:transition-transform after:duration-300 hover:after:scale-x-100 hover:after:origin-bottom-left">Contact</a>

                        <button
                            onClick={() => setIsSearchOpen(true)}
                            className="p-2 hover:bg-gray-100 rounded-full transition-colors text-text-main hover:text-brand"
                        >
                            <Search className="w-5 h-5" />
                        </button>
                    </nav>

                    {/* Mobile Menu Toggle */}
                    <div className="flex items-center gap-4 md:hidden">
                        <button
                            onClick={() => setIsSearchOpen(true)}
                            className="p-2 hover:bg-gray-100 rounded-full transition-colors"
                        >
                            <Search className="w-5 h-5" />
                        </button>
                        <button
                            className="p-2 z-50 relative"
                            onClick={() => setIsMenuOpen(!isMenuOpen)}
                        >
                            {isMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
                        </button>
                    </div>

                    {/* Mobile Nav Overlay */}
                    <div className={`fixed inset-0 bg-white z-40 flex flex-col items-center justify-center gap-8 transition-transform duration-300 transform ${isMenuOpen ? 'translate-x-0' : 'translate-x-full'} md:hidden`}>
                        <span className="absolute top-32 text-xs font-bold tracking-widest text-brand uppercase opacity-50">Menu</span>
                        <Link to="/" onClick={() => setIsMenuOpen(false)} className="text-3xl font-serif font-bold text-text-main hover:text-brand">Categories</Link>
                        <a href="#" onClick={() => setIsMenuOpen(false)} className="text-3xl font-serif font-bold text-text-main hover:text-brand">Explore</a>
                        <a href="#" onClick={() => setIsMenuOpen(false)} className="text-3xl font-serif font-bold text-text-main hover:text-brand">About</a>
                        <a href="#" onClick={() => setIsMenuOpen(false)} className="text-3xl font-serif font-bold text-text-main hover:text-brand">Contact</a>
                    </div>
                </div>
            </header>

            <SearchModal isOpen={isSearchOpen} onClose={() => setIsSearchOpen(false)} />
        </>
    );
}
