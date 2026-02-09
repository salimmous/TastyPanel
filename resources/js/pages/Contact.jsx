import { Mail, MapPin, Phone, Send, Clock, Globe } from 'lucide-react';

export default function Contact() {
    return (
        <div className="bg-white pb-20">
            {/* Cinematic Header */}
            <div className="relative h-[60vh] w-full overflow-hidden bg-black flex items-center justify-center mb-20">
                <div className="absolute inset-0 opacity-40">
                    <img src="https://images.unsplash.com/photo-1542010589005-d1eacc3918f2?ixlib=rb-4.0.3&auto=format&fit=crop&w=2400&q=80" className="w-full h-full object-cover" />
                </div>
                <div className="relative z-10 text-center text-white px-4 max-w-4xl">
                    <div className="inline-block px-4 py-2 bg-brand/20 backdrop-blur-md border border-brand/50 text-brand text-xs font-bold uppercase tracking-[0.2em] rounded-full mb-8">
                        We'd Love to Hear From You
                    </div>
                    <h1 className="text-6xl md:text-8xl font-serif font-bold mb-8">Get in Touch</h1>
                    <p className="text-xl md:text-2xl text-white/80 font-light leading-relaxed">
                        Have a question, collaboration idea, or just want to say hi? We're all ears.
                    </p>
                </div>
            </div>

            <div className="container mx-auto px-4 md:px-8">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-16 max-w-7xl mx-auto">

                    {/* Contact Info Sidebar */}
                    <div className="lg:col-span-4 space-y-12">
                        <div className="bg-[#FAF9F6] p-10 rounded-[2.5rem] space-y-8">
                            <h3 className="font-serif text-3xl font-bold mb-6">Contact Info</h3>

                            <div className="flex items-start gap-5 group cursor-pointer">
                                <div className="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-brand shadow-sm border border-gray-100 group-hover:scale-110 transition-transform">
                                    <Mail className="w-6 h-6" />
                                </div>
                                <div>
                                    <div className="text-xs font-bold uppercase tracking-widest text-text-muted mb-1">Email Us</div>
                                    <p className="text-lg font-bold text-text-main group-hover:text-brand transition-colors">hello@recipesbyclare.com</p>
                                    <p className="text-text-secondary text-sm mt-1">Response time: 24h</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-5 group cursor-pointer">
                                <div className="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-brand shadow-sm border border-gray-100 group-hover:scale-110 transition-transform">
                                    <Phone className="w-6 h-6" />
                                </div>
                                <div>
                                    <div className="text-xs font-bold uppercase tracking-widest text-text-muted mb-1">Call Us</div>
                                    <p className="text-lg font-bold text-text-main group-hover:text-brand transition-colors">+1 (555) 123-4567</p>
                                    <p className="text-text-secondary text-sm mt-1">Mon-Fri, 9am - 5pm EST</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-5 group cursor-pointer">
                                <div className="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-brand shadow-sm border border-gray-100 group-hover:scale-110 transition-transform">
                                    <MapPin className="w-6 h-6" />
                                </div>
                                <div>
                                    <div className="text-xs font-bold uppercase tracking-widest text-text-muted mb-1">Visit HQ</div>
                                    <p className="text-lg font-bold text-text-main group-hover:text-brand transition-colors">123 Culinary Avenue</p>
                                    <p className="text-text-secondary text-sm mt-1">New York, NY 10012</p>
                                </div>
                            </div>
                        </div>

                        <div className="bg-black text-white p-10 rounded-[2.5rem] relative overflow-hidden">
                            <div className="absolute top-0 right-0 w-32 h-32 bg-brand rounded-full blur-[60px] opacity-40" />
                            <h3 className="font-serif text-2xl font-bold mb-4 relative z-10">Collaborations</h3>
                            <p className="text-white/70 mb-6 font-light relative z-10">
                                Interested in partnering? We love working with brands that share our values.
                            </p>
                            <button className="px-6 py-3 bg-white/20 hover:bg-white/30 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-colors backdrop-blur-sm relative z-10">
                                Download Media Kit
                            </button>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="lg:col-span-8 bg-white rounded-[3rem] p-8 md:p-16 border border-gray-100 shadow-2xl shadow-gray-200/50">
                        <div className="max-w-2xl mx-auto">
                            <h2 className="font-serif text-4xl md:text-5xl font-bold mb-4">Send us a message</h2>
                            <p className="text-text-secondary mb-12 text-lg">We'd love to hear about your cooking journey or answer any questions.</p>

                            <form className="space-y-8">
                                <div className="grid md:grid-cols-2 gap-8">
                                    <div className="space-y-3">
                                        <label className="text-xs font-bold uppercase tracking-widest text-text-muted ml-2">First Name</label>
                                        <input type="text" className="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:outline-none focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 transition-all font-medium" placeholder="Jane" />
                                    </div>
                                    <div className="space-y-3">
                                        <label className="text-xs font-bold uppercase tracking-widest text-text-muted ml-2">Last Name</label>
                                        <input type="text" className="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:outline-none focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 transition-all font-medium" placeholder="Doe" />
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <label className="text-xs font-bold uppercase tracking-widest text-text-muted ml-2">Email Address</label>
                                    <input type="email" className="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:outline-none focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 transition-all font-medium" placeholder="jane@example.com" />
                                </div>

                                <div className="space-y-3">
                                    <label className="text-xs font-bold uppercase tracking-widest text-text-muted ml-2">Topic</label>
                                    <select className="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:outline-none focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 transition-all font-medium appearance-none">
                                        <option>General Inquiry</option>
                                        <option>Recipe Question</option>
                                        <option>Partnership</option>
                                        <option>Technical Issue</option>
                                    </select>
                                </div>

                                <div className="space-y-3">
                                    <label className="text-xs font-bold uppercase tracking-widest text-text-muted ml-2">Message</label>
                                    <textarea rows="6" className="w-full px-6 py-4 rounded-2xl bg-gray-50 border border-gray-100 focus:outline-none focus:border-brand focus:bg-white focus:ring-4 focus:ring-brand/10 transition-all resize-none font-medium text-lg leading-relaxed" placeholder="How can we help you?"></textarea>
                                </div>

                                <button className="w-full py-5 bg-black text-white font-bold uppercase tracking-[0.2em] rounded-2xl hover:bg-brand transition-all shadow-xl hover:shadow-2xl hover:-translate-y-1 flex items-center justify-center gap-3">
                                    Send Message <Send className="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
