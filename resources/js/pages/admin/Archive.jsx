import { useState } from 'react';
import { Archive as ArchiveIcon, Calendar, Search } from 'lucide-react';

export default function Archive() {
    const [searchTerm, setSearchTerm] = useState('');

    return (
        <div className="p-6">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Archive</h1>
            </div>

            <div className="bg-white rounded-lg shadow p-6">
                <div className="relative mb-6">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                    <input
                        type="text"
                        placeholder="Search archive..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div className="text-center py-12">
                    <ArchiveIcon className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p className="text-gray-500">Archive content will be displayed here</p>
                </div>
            </div>
        </div>
    );
}

