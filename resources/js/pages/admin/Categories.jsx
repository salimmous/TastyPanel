import { useState, useEffect } from 'react';
import { api } from '../../services/api';
import { Folder, Plus, Edit, Trash2, Search } from 'lucide-react';
import { canManageCategories, canDeleteContent, resolveRole } from '../../utils/permissions';

export default function Categories() {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentUser, setCurrentUser] = useState(null);

    useEffect(() => {
        loadCategories();
        loadUser();
    }, []);

    const loadUser = async () => {
        try {
            const response = await api.admin.getUser();
            setCurrentUser(response?.user || null);
        } catch {
            setCurrentUser(null);
        }
    };

    const loadCategories = async () => {
        try {
            const data = await api.admin.getCategories();
            setCategories(data);
        } catch (err) {
            console.error('Error loading categories:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        if (!canDeleteContent(currentUser)) {
            return;
        }
        if (!window.confirm('Are you sure you want to delete this category?')) {
            return;
        }
        try {
            await api.admin.deleteCategory(id);
            loadCategories();
        } catch (err) {
            console.error('Error deleting category:', err);
            alert('Failed to delete category');
        }
    };

    if (loading) {
        return <div className="p-6">جاري التحميل...</div>;
    }

    const canManage = canManageCategories(currentUser);
    const canDelete = canDeleteContent(currentUser);
    const role = resolveRole(currentUser);

    return (
        <div className="p-6">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Categories</h1>
                <button
                    className={`flex items-center gap-2 px-4 py-2 rounded-lg ${
                        canManage
                            ? 'bg-blue-600 text-white hover:bg-blue-700'
                            : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    }`}
                    disabled={!canManage}
                    title={canManage ? 'Add Category' : `Role ${role} cannot add categories`}
                >
                    <Plus className="w-4 h-4" />
                    Add Category
                </button>
            </div>

            <div className="bg-white rounded-lg shadow">
                <div className="p-4 border-b">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                        <input
                            type="text"
                            placeholder="Search categories..."
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recipes</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {categories.map((category) => (
                                <tr key={category.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{category.id}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{category.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{category.slug}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{category.recipes_count || 0}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                        <div className="flex items-center gap-2">
                                            <button
                                                className={`p-2 rounded ${
                                                    canManage ? 'text-blue-600 hover:bg-blue-50' : 'text-gray-400'
                                                }`}
                                                disabled={!canManage}
                                            >
                                                <Edit className="w-4 h-4" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(category.id)}
                                                className={`p-2 rounded ${
                                                    canDelete ? 'text-red-600 hover:bg-red-50' : 'text-gray-300'
                                                }`}
                                                disabled={!canDelete}
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
