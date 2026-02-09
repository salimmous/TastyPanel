export default function SectionHeader({ title }) {
    return (
        <div className="flex items-center gap-6 py-10 md:py-16 w-full overflow-hidden">
            <div className="h-px w-full bg-gray-200 relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-r from-transparent via-gray-400 to-transparent w-full h-full opacity-30"></div>
            </div>

            <h2 className="flex-shrink-0 text-2xl md:text-3xl font-serif font-bold uppercase tracking-widest text-text-main text-center px-4 relative">
                {title}
                <span className="block w-12 h-1 bg-brand mx-auto mt-2 rounded-full"></span>
            </h2>

            <div className="h-px w-full bg-gray-200 relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-r from-transparent via-gray-400 to-transparent w-full h-full opacity-30"></div>
            </div>
        </div>
    );
}
