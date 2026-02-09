import { useState } from 'react';
import { Megaphone, Plus, Edit, Trash2 } from 'lucide-react';

export default function Advertisement() {
    return (
        <div className="p-6">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Advertisement</h1>
                <button className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <Plus className="w-4 h-4" />
                    Add Advertisement
                </button>
            </div>

            <div className="bg-white rounded-lg shadow p-6">
                <div className="text-center py-12">
                    <Megaphone className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p className="text-gray-500">Advertisement management will be displayed here</p>
                </div>
            </div>
        </div>
    );
}

