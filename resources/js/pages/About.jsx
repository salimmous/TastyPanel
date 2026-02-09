import { ArrowRight, Mail, MapPin, Phone } from 'lucide-react';
import SectionHeader from '../components/SectionHeader';

export default function About() {
    return (
        <div className="container mx-auto px-4 md:px-8 pb-20">
            <div className="py-20 md:py-32 text-center max-w-4xl mx-auto">
                <div className="inline-block px-3 py-1 bg-brand/10 text-brand text-xs font-bold uppercase tracking-widest rounded-full mb-6">Our Story</div>
                <h1 className="text-5xl md:text-7xl font-serif font-bold text-text-main mb-8">Cooking with <span className="text-brand italic">Passion</span></h1>
                <p className="text-xl text-text-secondary leading-relaxed font-light">
                    Welcome to Recipes By Clare, a place where flavor meets simplicity. We believe that great food brings people together and that anyone can cook delicious meals with the right guidance.
                </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-20 items-center mb-24">
                <div className="relative aspect-[4/5] rounded-3xl overflow-hidden shadow-2xl skew-y-1 transform transition-transform hover:skew-y-0 duration-700">
                    <img src="https://images.unsplash.com/photo-1556910103-1c02745a30bf?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" alt="Kitchen" className="w-full h-full object-cover" />
                </div>
                <div>
                    <h2 className="text-3xl md:text-4xl font-serif font-bold mb-6">Meet The Team</h2>
                    <p className="text-text-secondary mb-6 leading-relaxed">
                        We are a small team of passionate foodies, photographers, and developers working together to bring you the best culinary experience possible. Every recipe is tested, tasted, and approved by our team before it reaches your screen.
                    </p>
                    <p className="text-text-secondary mb-8 leading-relaxed">
                        Founded in 2025, our mission is to make home cooking accessible, enjoyable, and inspiring for everyone—from complete beginners to seasoned home chefs.
                    </p>
                    <div className="grid grid-cols-2 gap-6 mb-8">
                        <div className="bg-gray-50 p-6 rounded-2xl text-center">
                            <div className="text-4xl font-bold text-brand mb-1">500+</div>
                            <div className="text-xs font-bold uppercase tracking-widest text-text-muted">Unique Recipes</div>
                        </div>
                        <div className="bg-gray-50 p-6 rounded-2xl text-center">
                            <div className="text-4xl font-bold text-brand mb-1">1M+</div>
                            <div className="text-xs font-bold uppercase tracking-widest text-text-muted">Happy Cooks</div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-brand-dark rounded-3xl p-12 md:p-20 text-center text-white relative overflow-hidden">
                <div className="relative z-10 max-w-2xl mx-auto">
                    <h2 className="text-3xl md:text-5xl font-serif font-bold mb-6">Share Your Journey</h2>
                    <p className="text-white/80 text-lg mb-10 font-light">
                        Have a recipe you'd like to share or a story about how food has impacted your life? We'd love to hear from you.
                    </p>
                    <button className="px-8 py-4 bg-white text-black font-bold uppercase tracking-widest rounded-xl hover:bg-gray-100 transition-colors inline-flex items-center gap-2">
                        Contact Us <ArrowRight className="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    );
}
