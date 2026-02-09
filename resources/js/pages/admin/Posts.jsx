import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../services/api';
import { FileText, Plus, Edit, Trash2, Search, X, Save, ArrowLeft } from 'lucide-react';
import { canDeleteContent, canManageCategories, canManageContent, canPublishContent, canReviewContent, resolveRole } from '../../utils/permissions';

export default function Posts() {
    const navigate = useNavigate();
    const [viewMode, setViewMode] = useState('list'); // 'list', 'create', 'edit'
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [filterType, setFilterType] = useState('all'); // all, recipe, article
    const [filterStatus, setFilterStatus] = useState('all'); // all, draft, review, approved, published
    const [currentPage, setCurrentPage] = useState(1);
    const [editingPost, setEditingPost] = useState(null);
    const [formData, setFormData] = useState({ type: 'recipe' });
    const [categories, setCategories] = useState([]);
    const [saving, setSaving] = useState(false);
    const [currentUser, setCurrentUser] = useState(null);

    useEffect(() => {
        if (viewMode === 'list') {
            loadPosts();
        }
        loadCategories();
        loadUser();
    }, [searchTerm, filterType, filterStatus, currentPage, viewMode]);

    const resolveDefaultStatus = (user) => (canPublishContent(user) ? 'published' : 'draft');

    useEffect(() => {
        if (!formData.status && currentUser) {
            setFormData((prev) => ({ ...prev, status: resolveDefaultStatus(currentUser) }));
        }
    }, [currentUser]);

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
        }
    };

    const loadPosts = async () => {
        setLoading(true);
        try {
            const statusParam = filterStatus !== 'all' ? filterStatus : undefined;
            const recipeParams = { search: searchTerm, page: currentPage };
            const articleParams = { search: searchTerm, page: currentPage };
            if (statusParam) {
                recipeParams.status = statusParam;
                articleParams.status = statusParam;
            }
            const [recipesData, articlesData] = await Promise.all([
                api.admin.getRecipes(recipeParams),
                api.admin.getArticles(articleParams),
            ]);

            let allPosts = [];

            // Add recipes
            if (filterType === 'all' || filterType === 'recipe') {
                // Handle paginated response (has data property) or direct array
                const recipes = recipesData?.data || (Array.isArray(recipesData) ? recipesData : []);
                if (Array.isArray(recipes) && recipes.length > 0) {
                    allPosts = allPosts.concat(
                        recipes.map(recipe => ({
                            ...recipe,
                            type: 'recipe',
                            typeLabel: 'Recipe',
                        }))
                    );
                }
            }

            // Add articles
            if (filterType === 'all' || filterType === 'article') {
                // Handle paginated response (has data property) or direct array
                const articles = articlesData?.data || (Array.isArray(articlesData) ? articlesData : []);
                if (Array.isArray(articles) && articles.length > 0) {
                    allPosts = allPosts.concat(
                        articles.map(article => ({
                            ...article,
                            type: 'article',
                            typeLabel: 'Article',
                        }))
                    );
                }
            }

            // Sort by created_at
            allPosts.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            setPosts(allPosts);
            console.log('✅ Posts loaded:', allPosts.length, 'total posts');
        } catch (err) {
            console.error('❌ Error loading posts:', err);
            setPosts([]);
        } finally {
            setLoading(false);
        }
    };

    const handleEdit = async (post) => {
        try {
            // Load full post data
            let fullPost;
            if (post.type === 'recipe') {
                fullPost = await api.admin.getRecipe(post.id);
            } else {
                fullPost = await api.admin.getArticle(post.id);
            }
            
            setEditingPost(post);
            setFormData({
                title: fullPost.title || '',
                description: fullPost.description || '',
                image: fullPost.image || '',
                status: fullPost.status || resolveDefaultStatus(currentUser),
                ...(post.type === 'recipe' && {
                    category_id: fullPost.category_id || '',
                    prep_time: fullPost.prep_time || '',
                    cook_time: fullPost.cook_time || '',
                    servings: fullPost.servings || '',
                    ingredients: Array.isArray(fullPost.ingredients) ? fullPost.ingredients.join('\n') : '',
                    instructions: Array.isArray(fullPost.instructions) ? fullPost.instructions.join('\n') : '',
                }),
            });
            setViewMode('edit');
        } catch (err) {
            console.error('Error loading post:', err);
            alert('Failed to load post data');
        }
    };

    const handleSave = async () => {
        if (!canManageContent(currentUser)) {
            return;
        }
        if (!editingPost) return;
        
        if (!formData.title) {
            alert('Title is required');
            return;
        }

        if (editingPost.type === 'recipe' && !formData.category_id) {
            alert('Category is required for recipes');
            return;
        }
        
        setSaving(true);
        try {
            const data = { ...formData };
            
            // Convert ingredients and instructions to arrays for recipes
            if (editingPost.type === 'recipe') {
                if (data.ingredients) {
                    data.ingredients = data.ingredients.split('\n').filter(line => line.trim());
                }
                if (data.instructions) {
                    data.instructions = data.instructions.split('\n').filter(line => line.trim());
                }
            }
            
            let response;
            if (editingPost.type === 'recipe') {
                response = await api.admin.updateRecipe(editingPost.id, data);
                console.log('✅ Recipe updated successfully:', response);
            } else {
                response = await api.admin.updateArticle(editingPost.id, data);
                console.log('✅ Article updated successfully:', response);
            }
            
            setEditingPost(null);
            setFormData({ type: 'recipe', status: resolveDefaultStatus(currentUser) });
            setViewMode('list');
            loadPosts();
        } catch (err) {
            console.error('❌ Error updating post:', err);
            alert('Failed to update post: ' + (err.message || 'Unknown error'));
        } finally {
            setSaving(false);
        }
    };

    const handleCreate = async () => {
        if (!canManageContent(currentUser)) {
            return;
        }
        if (!formData.title) {
            alert('Title is required');
            return;
        }

        if (formData.type === 'recipe' && !formData.category_id) {
            alert('Category is required for recipes');
            return;
        }

        setSaving(true);
        try {
            const data = { ...formData };
            if (!data.status) {
                data.status = resolveDefaultStatus(currentUser);
            }
            
            // Convert ingredients and instructions to arrays for recipes
            if (data.type === 'recipe') {
                if (data.ingredients) {
                    data.ingredients = data.ingredients.split('\n').filter(line => line.trim());
                }
                if (data.instructions) {
                    data.instructions = data.instructions.split('\n').filter(line => line.trim());
                }
                // Remove type from data before sending
                const { type, ...recipeData } = data;
                const response = await api.admin.createRecipe(recipeData);
                console.log('✅ Recipe created successfully:', response);
            } else {
                // Remove type from data before sending
                const { type, ...articleData } = data;
                const response = await api.admin.createArticle(articleData);
                console.log('✅ Article created successfully:', response);
            }
            
            setFormData({ type: 'recipe', status: resolveDefaultStatus(currentUser) });
            setViewMode('list');
            loadPosts();
        } catch (err) {
            console.error('❌ Error creating post:', err);
            alert('Failed to create post: ' + (err.message || 'Unknown error'));
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (post) => {
        if (!canDeleteContent(currentUser)) {
            return;
        }
        if (!window.confirm(`Are you sure you want to delete this ${post.type}?`)) {
            return;
        }
        try {
            let response;
            if (post.type === 'recipe') {
                response = await api.admin.deleteRecipe(post.id);
                console.log('✅ Recipe deleted successfully:', response);
            } else {
                response = await api.admin.deleteArticle(post.id);
                console.log('✅ Article deleted successfully:', response);
            }
            loadPosts();
        } catch (err) {
            console.error('❌ Error deleting post:', err);
            alert('Failed to delete post: ' + (err.message || 'Unknown error'));
        }
    };

    const handleNewPost = () => {
        if (!canManageContent(currentUser)) {
            return;
        }
        setFormData({ type: 'recipe', status: resolveDefaultStatus(currentUser) });
        setEditingPost(null);
        setViewMode('create');
    };

    const handleCancel = () => {
        setFormData({ type: 'recipe', status: resolveDefaultStatus(currentUser) });
        setEditingPost(null);
        setViewMode('list');
    };

    // Render Editor (WordPress Style) - Used for both Create and Edit
    const renderEditor = () => {
        const isEdit = viewMode === 'edit';
        const postType = isEdit ? editingPost.type : formData.type;
        const statusOptions = [
            { value: 'draft', label: 'Draft', enabled: true },
            { value: 'review', label: 'In Review', enabled: canReviewContent(currentUser) },
            { value: 'approved', label: 'Approved', enabled: canPublishContent(currentUser) },
            { value: 'published', label: 'Published', enabled: canPublishContent(currentUser) },
        ];

        return (
            <div className="flex-1 flex flex-col bg-gray-100">
                {/* Top Bar - WordPress Style */}
                <div className="bg-white border-b border-gray-300 px-4 py-3 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <button
                            onClick={handleCancel}
                            className="p-2 hover:bg-gray-100 rounded"
                        >
                            <ArrowLeft className="w-5 h-5 text-gray-600" />
                        </button>
                        <h1 className="text-lg font-semibold text-gray-900">
                            {isEdit ? 'Edit Post' : 'Create New Post'}
                        </h1>
                        <span className="text-sm text-gray-500">
                            {postType === 'recipe' ? 'Recipe' : 'Article'}
                        </span>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={isEdit ? handleSave : handleCreate}
                            disabled={saving || !canManageContent(currentUser)}
                            className={`px-4 py-2 rounded flex items-center gap-2 text-sm font-medium ${
                                canManageContent(currentUser)
                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                    : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                            }`}
                            title={canManageContent(currentUser) ? '' : `Role ${resolveRole(currentUser)} cannot publish`}
                        >
                            {saving ? (
                                <>
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                    {isEdit ? 'Updating...' : 'Creating...'}
                                </>
                            ) : (
                                <>
                                    <Save className="w-4 h-4" />
                                    {isEdit ? 'Update' : 'Publish'}
                                </>
                            )}
                        </button>
                    </div>
                </div>

                {/* Main Content Area - WordPress Style */}
                <div className="flex-1 overflow-hidden flex">
                    {/* Editor Area - Left Side */}
                    <div className="flex-1 overflow-y-auto bg-white">
                        <div className="max-w-4xl mx-auto p-6">
                            {/* Post Type Selection - Only for Create */}
                            {!isEdit && (
                                <div className="mb-6 border border-gray-300 rounded p-4 bg-gray-50">
                                    <label className="block text-sm font-semibold text-gray-700 mb-3">
                                        Post Type *
                                    </label>
                                    <div className="flex gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setFormData({ ...formData, type: 'recipe' })}
                                            className={`flex-1 px-4 py-3 rounded-lg border-2 transition-colors ${
                                                formData.type === 'recipe'
                                                    ? 'bg-blue-600 text-white border-blue-600'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'
                                            }`}
                                        >
                                            <div className="flex items-center justify-center gap-2">
                                                <span className="text-lg">✓</span>
                                                <span className="font-medium">Recipe</span>
                                            </div>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setFormData({ ...formData, type: 'article' })}
                                            className={`flex-1 px-4 py-3 rounded-lg border-2 transition-colors ${
                                                formData.type === 'article'
                                                    ? 'bg-blue-600 text-white border-blue-600'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'
                                            }`}
                                        >
                                            <div className="flex items-center justify-center gap-2">
                                                <span className="text-lg">✓</span>
                                                <span className="font-medium">Article</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Title - WordPress Style */}
                            <div className="mb-6">
                                <input
                                    type="text"
                                    value={formData.title || ''}
                                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                    className="w-full text-2xl font-semibold border-0 focus:ring-0 p-0 mb-2 placeholder-gray-400"
                                    placeholder="Add title"
                                    required
                                    style={{ fontSize: '1.5rem', lineHeight: '1.75rem' }}
                                />
                                <div className="text-sm text-gray-500">
                                    Permalink: <span className="text-blue-600">{formData.title ? formData.title.toLowerCase().replace(/\s+/g, '-') : '...'}</span>
                                </div>
                            </div>

                            {/* Editor Toolbar - WordPress Style */}
                            <div className="border border-gray-300 rounded-t bg-gray-50 p-2 flex items-center gap-2 mb-0">
                                <button type="button" className="px-3 py-1 text-sm border border-gray-300 bg-white rounded hover:bg-gray-50 font-semibold">
                                    B
                                </button>
                                <button type="button" className="px-3 py-1 text-sm border border-gray-300 bg-white rounded hover:bg-gray-50 italic">
                                    I
                                </button>
                                <button type="button" className="px-3 py-1 text-sm border border-gray-300 bg-white rounded hover:bg-gray-50">
                                    U
                                </button>
                                <div className="w-px h-6 bg-gray-300 mx-1"></div>
                                <button type="button" className="px-3 py-1 text-sm border border-gray-300 bg-white rounded hover:bg-gray-50">
                                    Link
                                </button>
                                <button type="button" className="px-3 py-1 text-sm border border-gray-300 bg-white rounded hover:bg-gray-50">
                                    Image
                                </button>
                                <div className="flex-1"></div>
                                <button type="button" className="px-3 py-1 text-xs text-gray-600 border border-gray-300 bg-white rounded hover:bg-gray-50">
                                    Visual
                                </button>
                                <button type="button" className="px-3 py-1 text-xs text-gray-600 border border-gray-300 bg-white rounded hover:bg-gray-50">
                                    Text
                                </button>
                            </div>

                            {/* Content Editor - WordPress Style */}
                            <div className="border-x border-gray-300">
                                <textarea
                                    value={formData.description || ''}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    rows={20}
                                    className="w-full p-4 border-0 focus:ring-0 resize-none font-sans text-base leading-relaxed"
                                    placeholder="Start writing or type / to choose a block"
                                    style={{ minHeight: '400px' }}
                                />
                            </div>

                            {/* Image URL */}
                            <div className="mt-6 border border-gray-300 rounded p-4 bg-gray-50">
                                <label className="block text-sm font-semibold text-gray-700 mb-2">
                                    Featured Image
                                </label>
                                <input
                                    type="url"
                                    value={formData.image || ''}
                                    onChange={(e) => setFormData({ ...formData, image: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="https://example.com/image.jpg"
                                />
                                {formData.image && (
                                    <div className="mt-3">
                                        <img src={formData.image} alt="Preview" className="max-w-full h-auto rounded border border-gray-300" />
                                    </div>
                                )}
                            </div>

                            {/* Recipe-specific fields */}
                            {postType === 'recipe' && (
                                <div className="mt-6 space-y-6">
                                    <div className="border border-gray-300 rounded p-4 bg-gray-50">
                                        <h3 className="text-sm font-semibold text-gray-700 mb-4">Recipe Details</h3>
                                        <div className="grid grid-cols-3 gap-4">
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                                    Category *
                                                </label>
                                                <select
                                                    value={formData.category_id || ''}
                                                    onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    required
                                                >
                                                    <option value="">Select Category</option>
                                                    {categories.map((cat) => (
                                                        <option key={cat.id} value={cat.id}>
                                                            {cat.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                                    Prep Time (min)
                                                </label>
                                                <input
                                                    type="number"
                                                    value={formData.prep_time || ''}
                                                    onChange={(e) => setFormData({ ...formData, prep_time: e.target.value })}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                                    Cook Time (min)
                                                </label>
                                                <input
                                                    type="number"
                                                    value={formData.cook_time || ''}
                                                    onChange={(e) => setFormData({ ...formData, cook_time: e.target.value })}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                />
                                            </div>
                                        </div>
                                        <div className="mt-4">
                                            <label className="block text-xs font-medium text-gray-600 mb-1">
                                                Servings
                                            </label>
                                            <input
                                                type="number"
                                                value={formData.servings || ''}
                                                onChange={(e) => setFormData({ ...formData, servings: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            />
                                        </div>
                                    </div>

                                    <div className="border border-gray-300 rounded p-4 bg-gray-50">
                                        <h3 className="text-sm font-semibold text-gray-700 mb-4">Ingredients</h3>
                                        <textarea
                                            value={formData.ingredients || ''}
                                            onChange={(e) => setFormData({ ...formData, ingredients: e.target.value })}
                                            rows={8}
                                            className="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                            placeholder="2 cups flour&#10;1 cup sugar&#10;3 eggs&#10;..."
                                        />
                                    </div>

                                    <div className="border border-gray-300 rounded p-4 bg-gray-50">
                                        <h3 className="text-sm font-semibold text-gray-700 mb-4">Instructions</h3>
                                        <textarea
                                            value={formData.instructions || ''}
                                            onChange={(e) => setFormData({ ...formData, instructions: e.target.value })}
                                            rows={10}
                                            className="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                            placeholder="Step 1: Preheat oven&#10;Step 2: Mix ingredients&#10;..."
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Sidebar - WordPress Style */}
                    <div className="w-80 bg-gray-50 border-l border-gray-300 overflow-y-auto">
                        <div className="p-4 space-y-4">
                            {/* Publish Box */}
                            <div className="bg-white border border-gray-300 rounded">
                                <div className="px-4 py-3 border-b border-gray-300 bg-gray-50">
                                    <h2 className="text-sm font-semibold text-gray-700">Publish</h2>
                                </div>
                                <div className="p-4 space-y-3">
                                    {isEdit && (
                                        <>
                                            <div className="text-xs text-gray-600">
                                                <strong>Status:</strong> {formData.status || 'draft'}
                                            </div>
                                            <div className="text-xs text-gray-600">
                                                <strong>Visibility:</strong> Public
                                            </div>
                                            <div className="text-xs text-gray-600">
                                                <strong>Published:</strong> {new Date(editingPost.created_at).toLocaleString()}
                                            </div>
                                        </>
                                    )}
                                    <div className="pt-3 border-t border-gray-300">
                                        <label className="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                        <select
                                            value={formData.status || resolveDefaultStatus(currentUser)}
                                            onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded bg-white text-sm"
                                        >
                                            {statusOptions.map((option) => (
                                                <option key={option.value} value={option.value} disabled={!option.enabled}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="pt-3 border-t border-gray-300">
                                        <button
                                            onClick={isEdit ? handleSave : handleCreate}
                                            disabled={saving || !canManageContent(currentUser)}
                                            className={`w-full px-4 py-2 rounded flex items-center justify-center gap-2 text-sm font-medium ${
                                                canManageContent(currentUser)
                                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                                    : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                            }`}
                                            title={
                                                canManageContent(currentUser)
                                                    ? ''
                                                    : `Role ${resolveRole(currentUser)} cannot publish`
                                            }
                                        >
                                            {saving ? (
                                                <>
                                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                                    {isEdit ? 'Updating...' : 'Creating...'}
                                                </>
                                            ) : (
                                                isEdit ? 'Update' : 'Publish'
                                            )}
                                        </button>
                                    </div>
                                    <div>
                                        <button
                                            onClick={handleCancel}
                                            className="w-full px-4 py-2 text-red-600 border border-red-300 rounded hover:bg-red-50 text-sm"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Categories - WordPress Style */}
                            {postType === 'recipe' && (
                                <div className="bg-white border border-gray-300 rounded mb-4">
                                    <div className="px-4 py-3 border-b border-gray-300 bg-gray-50">
                                        <h2 className="text-sm font-semibold text-gray-700">Categories</h2>
                                    </div>
                                    <div className="p-4">
                                        <div className="space-y-2 max-h-48 overflow-y-auto">
                                            {categories.map((cat) => (
                                                <label key={cat.id} className="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                                    <input
                                                        type="radio"
                                                        name="category"
                                                        value={cat.id}
                                                        checked={formData.category_id == cat.id}
                                                        onChange={(e) => setFormData({ ...formData, category_id: parseInt(e.target.value) })}
                                                        className="w-4 h-4 text-blue-600"
                                                    />
                                                    <span className="text-sm text-gray-700">{cat.name}</span>
                                                </label>
                                            ))}
                                        </div>
                                        <button 
                                            onClick={() => navigate('/admin/categories')}
                                            className={`mt-3 text-xs ${
                                                canManageCategories(currentUser)
                                                    ? 'text-blue-600 hover:text-blue-700'
                                                    : 'text-gray-300 cursor-not-allowed'
                                            }`}
                                            disabled={!canManageCategories(currentUser)}
                                        >
                                            + Add New Category
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Featured Image - WordPress Style */}
                            <div className="bg-white border border-gray-300 rounded">
                                <div className="px-4 py-3 border-b border-gray-300 bg-gray-50">
                                    <h2 className="text-sm font-semibold text-gray-700">Featured Image</h2>
                                </div>
                                <div className="p-4">
                                    {formData.image ? (
                                        <div>
                                            <img src={formData.image} alt="Featured" className="w-full h-auto rounded border border-gray-300 mb-2" />
                                            <input
                                                type="url"
                                                value={formData.image || ''}
                                                onChange={(e) => setFormData({ ...formData, image: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm mb-2"
                                                placeholder="Image URL"
                                            />
                                            <button
                                                onClick={() => setFormData({ ...formData, image: '' })}
                                                className="text-sm text-red-600 hover:text-red-700"
                                            >
                                                Remove featured image
                                            </button>
                                        </div>
                                    ) : (
                                        <button className="w-full py-8 border-2 border-dashed border-gray-300 rounded hover:border-blue-500 text-sm text-gray-600 hover:text-blue-600">
                                            Set featured image
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    // Render List View
    const renderListView = () => {
        if (loading) {
            return (
                <div className="p-6 flex items-center justify-center min-h-screen">
                    <div className="text-center">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p className="mt-4 text-gray-600">جاري التحميل...</p>
                    </div>
                </div>
            );
        }

        return (
            <div className="p-6">
                <div className="flex items-center justify-between mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Posts</h1>
                    <div className="flex items-center gap-2">
                        <select
                            value={filterType}
                            onChange={(e) => setFilterType(e.target.value)}
                            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">All Posts</option>
                            <option value="recipe">Recipes Only</option>
                            <option value="article">Articles Only</option>
                        </select>
                        <select
                            value={filterStatus}
                            onChange={(e) => setFilterStatus(e.target.value)}
                            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="all">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="review">In Review</option>
                            <option value="approved">Approved</option>
                            <option value="published">Published</option>
                        </select>
                        <button 
                            onClick={handleNewPost}
                            className={`flex items-center gap-2 px-4 py-2 rounded-lg ${
                                canManageContent(currentUser)
                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                    : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                            }`}
                            disabled={!canManageContent(currentUser)}
                        >
                            <Plus className="w-4 h-4" />
                            New Post
                        </button>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow">
                    <div className="p-4 border-b">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input
                                type="text"
                                placeholder="Search posts..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {posts.length > 0 ? (
                                    posts.map((post) => (
                                        <tr key={`${post.type}-${post.id}`} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{post.id}</td>
                                            <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                                <div className="flex items-center gap-2">
                                                    <FileText className="w-4 h-4 text-gray-400" />
                                                    <span className="truncate max-w-xs">{post.title}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs rounded-full ${
                                                    post.type === 'recipe' 
                                                        ? 'bg-blue-100 text-blue-700' 
                                                        : 'bg-green-100 text-green-700'
                                                }`}>
                                                    {post.typeLabel}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {post.category?.name || (post.type === 'article' ? 'N/A' : 'Uncategorized')}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs rounded-full ${
                                                    (post.status || 'published') === 'published'
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : (post.status === 'approved'
                                                            ? 'bg-indigo-100 text-indigo-700'
                                                            : (post.status === 'review'
                                                                ? 'bg-amber-100 text-amber-700'
                                                                : 'bg-gray-100 text-gray-700'))
                                                }`}>
                                                    {post.status || 'published'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(post.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <div className="flex items-center gap-2">
                                                    <button 
                                                        onClick={() => handleEdit(post)}
                                                        className="p-2 text-blue-600 hover:bg-blue-50 rounded">
                                                        <Edit className="w-4 h-4" />
                                                    </button>
                                                    <button 
                                                        onClick={() => handleDelete(post)}
                                                        className={`p-2 rounded ${
                                                            canDeleteContent(currentUser)
                                                                ? 'text-red-600 hover:bg-red-50'
                                                                : 'text-gray-300'
                                                        }`}
                                                        disabled={!canDeleteContent(currentUser)}
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="7" className="px-6 py-8 text-center text-gray-500">
                                            No posts found
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        );
    };

    // Main render - switch between views
    if (viewMode === 'create' || viewMode === 'edit') {
        return renderEditor();
    }

    return renderListView();
}
