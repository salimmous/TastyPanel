import { useState } from 'react';
import { Rss, Plus, RefreshCw } from 'lucide-react';

export default function RssFeeds() {
    return (
        <div className="p-6">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">RSS Feeds</h1>
                <button className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <Plus className="w-4 h-4" />
                    Add RSS Feed
                </button>
            </div>

            <div className="bg-white rounded-lg shadow p-6">
                <div className="text-center py-12">
                    <Rss className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p className="text-gray-500">RSS Feeds management will be displayed here</p>
                </div>
            </div>
        </div>
    );
}

