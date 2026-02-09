import { Facebook, Twitter, Instagram, Mail, Search, Map, Rss } from 'lucide-react';
import { Link } from 'react-router-dom';

export default function Footer() {
    return (
        <footer className="bg-[#FAF9F6] border-t border-dashed border-gray-300 mt-20 pt-12 pb-8">
            <div className="container mx-auto px-4 text-center">

                <div className="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto mb-12 text-left">
                    <div>
                        <h4 className="font-bold text-text-main mb-4 uppercase text-xs tracking-widest">Discover</h4>
                        <div className="flex flex-col gap-3 text-sm text-text-secondary">
                            <Link to="/" className="hover:text-brand transition-colors">Home</Link>
                            <Link to="/" className="hover:text-brand transition-colors">Recipes</Link>
                            <Link to="/" className="hover:text-brand transition-colors">Articles</Link>
                            <Link to="/" className="hover:text-brand transition-colors">Videos</Link>
                        </div>
                    </div>
                    <div>
                        <h4 className="font-bold text-text-main mb-4 uppercase text-xs tracking-widest">Company</h4>
                        <div className="flex flex-col gap-3 text-sm text-text-secondary">
                            <Link to="/about" className="hover:text-brand transition-colors">About Us</Link>
                            <Link to="/contact" className="hover:text-brand transition-colors">Contact</Link>
                            <Link to="#" className="hover:text-brand transition-colors">Careers</Link>
                            <Link to="#" className="hover:text-brand transition-colors">Team</Link>
                        </div>
                    </div>
                    <div>
                        <h4 className="font-bold text-text-main mb-4 uppercase text-xs tracking-widest">Legal</h4>
                        <div className="flex flex-col gap-3 text-sm text-text-secondary">
                            <Link to="#" className="hover:text-brand transition-colors">Privacy Policy</Link>
                            <Link to="#" className="hover:text-brand transition-colors">Terms of Service</Link>
                            <Link to="#" className="hover:text-brand transition-colors">Cookie Policy</Link>
                        </div>
                    </div>
                    <div>
                        <h4 className="font-bold text-text-main mb-4 uppercase text-xs tracking-widest">Socials</h4>
                        <div className="flex flex-col gap-3 text-sm text-text-secondary">
                            <a href="#" className="hover:text-brand transition-colors">Instagram</a>
                            <a href="#" className="hover:text-brand transition-colors">Twitter</a>
                            <a href="#" className="hover:text-brand transition-colors">Facebook</a>
                            <a href="#" className="hover:text-brand transition-colors">Pinterest</a>
                        </div>
                    </div>
                </div>

                <div className="flex justify-center gap-6 mb-12 text-sm font-medium text-text-secondary">
                    <a href="#" className="flex items-center gap-2 hover:text-brand"><Search className="w-4 h-4" /> Search</a>
                    <a href="#" className="flex items-center gap-2 hover:text-brand"><Map className="w-4 h-4" /> Sitemap</a>
                    <a href="#" className="flex items-center gap-2 hover:text-brand"><Rss className="w-4 h-4" /> Feed</a>
                </div>

                <div className="flex justify-center gap-4 mb-6">
                    <a href="#" className="bg-black text-white p-2 rounded-full hover:bg-brand transition-colors"><Facebook className="w-5 h-5" /></a>
                    <a href="#" className="bg-black text-white p-2 rounded-full hover:bg-brand transition-colors"><Twitter className="w-5 h-5" /></a>
                    <a href="#" className="bg-black text-white p-2 rounded-full hover:bg-brand transition-colors"><Instagram className="w-5 h-5" /></a>
                    <a href="#" className="bg-black text-white p-2 rounded-full hover:bg-brand transition-colors"><Mail className="w-5 h-5" /></a>
                </div>

                <div className="text-xs text-text-muted">
                    <p>© 2025 Recipes by Clare. All rights reserved.</p>
                    <p className="mt-1">v3.0.1</p>
                </div>
            </div>
        </footer>
    );
}
