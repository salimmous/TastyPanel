import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function Pagination() {
    return (
        <div className="flex justify-center items-center gap-3 mt-16 mb-8 font-sans">
            <button
                disabled
                className="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-200 text-text-muted transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:border-brand hover:text-brand"
            >
                <ChevronLeft className="w-5 h-5" />
            </button>

            {/* Page Numbers */}
            <button className="w-10 h-10 flex items-center justify-center rounded-full bg-brand text-white font-bold shadow-lg shadow-brand/30 transform scale-110">
                1
            </button>
            <button className="w-10 h-10 flex items-center justify-center rounded-full bg-white text-text-main font-medium border border-transparent hover:border-gray-200 hover:bg-gray-50 transition-colors">
                2
            </button>
            <button className="w-10 h-10 flex items-center justify-center rounded-full bg-white text-text-main font-medium border border-transparent hover:border-gray-200 hover:bg-gray-50 transition-colors">
                3
            </button>
            <span className="text-text-muted px-2">...</span>
            <button className="w-10 h-10 flex items-center justify-center rounded-full bg-white text-text-main font-medium border border-transparent hover:border-gray-200 hover:bg-gray-50 transition-colors">
                12
            </button>

            <button className="w-10 h-10 flex items-center justify-center rounded-full bg-black text-white hover:bg-brand transition-colors shadow-md hover:shadow-lg">
                <ChevronRight className="w-5 h-5" />
            </button>
        </div>
    );
}
