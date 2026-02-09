import { Star, ThumbsUp } from 'lucide-react';

export default function Reviews({ recipeTitle }) {
    const reviews = [
        {
            id: 1,
            author: "Sarah Jenkins",
            date: "2 days ago",
            rating: 5,
            content: "Absolutely delicious! The apples added such a nice sweetness to the savory chicken. Will definitely make this again.",
            likes: 12
        },
        {
            id: 2,
            author: "Mike T.",
            date: "1 week ago",
            rating: 5,
            content: "I was skeptical about the brie melting out, but the toothpick tip worked perfectly. My family loved it.",
            likes: 8
        },
        {
            id: 3,
            author: "Emily R.",
            date: "2 weeks ago",
            rating: 4,
            content: "Great recipe, but needed a bit more cooking time for me. Maybe my chicken breasts were too thick. Flavor was 10/10 though!",
            likes: 3
        }
    ];

    return (
        <div className="bg-white pt-10 pb-12 border-t border-gray-100" id="reviews">
            <div className="flex items-center justify-between mb-10">
                <h3 className="font-serif text-3xl font-bold text-text-main">Reviews & Comments</h3>
                <div className="flex items-center gap-2">
                    <div className="flex text-yellow-400">
                        {[...Array(5)].map((_, i) => <Star key={i} className="w-5 h-5 fill-current" />)}
                    </div>
                    <span className="font-bold text-lg">4.9</span>
                    <span className="text-text-muted text-sm">(42 reviews)</span>
                </div>
            </div>

            <div className="grid lg:grid-cols-12 gap-12">
                {/* Form */}
                <div className="lg:col-span-5 bg-gray-50 p-8 rounded-2xl h-fit">
                    <h4 className="font-bold text-xl mb-4">Leave a Review</h4>
                    <p className="text-text-secondary text-sm mb-6">Have you made this recipe? We'd love to hear your thoughts!</p>

                    <form className="space-y-4">
                        <div>
                            <label className="text-xs font-bold uppercase tracking-widest text-text-muted block mb-2">Rating</label>
                            <div className="flex gap-2 text-gray-300">
                                {[...Array(5)].map((_, i) => <Star key={i} className="w-8 h-8 hover:text-yellow-400 cursor-pointer transition-colors" />)}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <input type="text" placeholder="Name" className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-brand" />
                            <input type="email" placeholder="Email" className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-brand" />
                        </div>

                        <textarea rows="4" placeholder="Share your tips or feedback..." className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-brand resize-none"></textarea>

                        <button className="w-full py-3 bg-brand text-white font-bold rounded-xl hover:bg-brand-dark transition-colors shadow-lg shadow-brand/20">
                            Post Review
                        </button>
                    </form>
                </div>

                {/* List */}
                <div className="lg:col-span-7 space-y-8">
                    {reviews.map(review => (
                        <div key={review.id} className="border-b border-gray-100 last:border-0 pb-8 last:pb-0">
                            <div className="flex justify-between items-start mb-3">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center font-serif font-bold text-text-secondary">
                                        {review.author[0]}
                                    </div>
                                    <div>
                                        <div className="font-bold text-text-main">{review.author}</div>
                                        <div className="text-xs text-text-muted">{review.date}</div>
                                    </div>
                                </div>
                                <div className="flex text-yellow-400">
                                    {[...Array(review.rating)].map((_, i) => <Star key={i} className="w-4 h-4 fill-current" />)}
                                </div>
                            </div>
                            <p className="text-text-secondary leading-relaxed mb-4">{review.content}</p>
                            <button className="flex items-center gap-2 text-xs text-text-muted hover:text-brand transition-colors">
                                <ThumbsUp className="w-4 h-4" /> Helpful ({review.likes})
                            </button>
                        </div>
                    ))}
                    <button className="w-full py-2 text-brand font-bold text-sm tracking-widest uppercase hover:underline">
                        Load More Reviews
                    </button>
                </div>
            </div>
        </div>
    );
}
