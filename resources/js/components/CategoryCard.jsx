import { Link } from 'react-router-dom';

export default function CategoryCard({ category }) {
    return (
        <Link
            to={`/category/${category.id}`}
            className="group relative overflow-hidden rounded-xl aspect-[16/9] bg-gray-100 shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 block"
        >
            <img
                src={category.image}
                alt={category.name}
                className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-80 group-hover:opacity-90 transition-opacity" />

            <div className="absolute bottom-4 left-4 right-4">
                <h3 className="text-white font-extrabold text-xl md:text-2xl tracking-wide font-sans drop-shadow-lg"
                    style={{ textShadow: '2px 2px 0px rgba(0,0,0,0.5), -1px -1px 0 #000' }}>
                    {category.name}
                </h3>
            </div>
        </Link>
    );
}
