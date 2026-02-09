# Recipe Website - Laravel Backend Conversion

This project has been successfully converted to use Laravel as the backend API with React as the frontend.

## Project Structure

```
9bie/
├── laravel-backend/          # Laravel API backend
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── CategoryController.php
│   │   │   ├── RecipeController.php
│   │   │   └── ArticleController.php
│   │   └── Models/
│   │       ├── Category.php
│   │       ├── Recipe.php
│   │       └── Article.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
│       └── api.php
├── src/                      # React frontend
│   ├── components/
│   ├── pages/
│   ├── services/
│   │   └── api.js           # API service layer
│   └── data.js              # Legacy data (can be removed)
└── README-LARAVEL.md        # This file
```

## Features

### Backend (Laravel)
- ✅ RESTful API endpoints
- ✅ Database models with relationships
- ✅ Migrations for database schema
- ✅ Seeders with existing data
- ✅ CORS enabled for React frontend
- ✅ API resource controllers

### Frontend (React)
- ✅ API service layer for backend communication
- ✅ Existing React components (unchanged)
- ✅ Ready to integrate with Laravel API

## Database Schema

### Categories Table
- `id` - Primary key
- `slug` - Unique identifier
- `name` - Category name
- `image` - Image URL
- `description` - Category description
- `timestamps`

### Recipes Table
- `id` - Primary key
- `slug` - Unique identifier
- `category_id` - Foreign key to categories
- `title` - Recipe title
- `description` - Recipe description
- `image` - Image URL
- `prep_time` - Preparation time
- `cook_time` - Cooking time
- `servings` - Number of servings
- `ingredients` - JSON array
- `instructions` - JSON array
- `nutrition` - JSON object (nullable)
- `timestamps`

### Articles Table
- `id` - Primary key
- `slug` - Unique identifier
- `title` - Article title
- `description` - Article description
- `image` - Image URL
- `timestamps`

## API Endpoints

### Categories
- `GET /api/categories` - Get all categories with recipes
- `GET /api/categories/{slug}` - Get single category by slug
- `POST /api/categories` - Create new category
- `PUT /api/categories/{id}` - Update category
- `DELETE /api/categories/{id}` - Delete category

### Recipes
- `GET /api/recipes` - Get all recipes
- `GET /api/recipes?category={slug}` - Filter recipes by category
- `GET /api/recipes?search={term}` - Search recipes
- `GET /api/recipes/{slug}` - Get single recipe by slug
- `POST /api/recipes` - Create new recipe
- `PUT /api/recipes/{id}` - Update recipe
- `DELETE /api/recipes/{id}` - Delete recipe

### Articles
- `GET /api/articles` - Get all articles
- `GET /api/articles/{slug}` - Get single article by slug
- `POST /api/articles` - Create new article
- `PUT /api/articles/{id}` - Update article
- `DELETE /api/articles/{id}` - Delete article

## Setup Instructions

### Backend Setup

1. **Navigate to Laravel backend:**
   ```bash
   cd laravel-backend
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations and seed database:**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Start Laravel development server:**
   ```bash
   php artisan serve
   ```
   The API will be available at `http://127.0.0.1:8000`

### Frontend Setup

1. **Navigate to project root:**
   ```bash
   cd /Users/salim/Downloads/9bie
   ```

2. **Install dependencies (if not already done):**
   ```bash
   npm install
   ```

3. **Start React development server:**
   ```bash
   npm run dev
   ```

## Using the API in React

The API service is located at `src/services/api.js`. Here's how to use it:

### Example: Fetch all categories
```javascript
import api from './services/api';

// In your component
useEffect(() => {
    const fetchCategories = async () => {
        const categories = await api.getCategories();
        setCategories(categories);
    };
    fetchCategories();
}, []);
```

### Example: Fetch recipes by category
```javascript
import api from './services/api';

const categorySlug = 'evening-meals';
const recipes = await api.getRecipesByCategory(categorySlug);
```

### Example: Search recipes
```javascript
import api from './services/api';

const searchTerm = 'chicken';
const results = await api.searchRecipes(searchTerm);
```

## Migration Steps for Existing Components

To convert your existing React components to use the Laravel API:

1. **Import the API service:**
   ```javascript
   import api from '../services/api';
   ```

2. **Replace static data imports:**
   ```javascript
   // Old way
   import { categories, recipes } from '../data';
   
   // New way - fetch from API
   const [categories, setCategories] = useState([]);
   const [recipes, setRecipes] = useState([]);
   
   useEffect(() => {
       const fetchData = async () => {
           const categoriesData = await api.getCategories();
           const recipesData = await api.getRecipes();
           setCategories(categoriesData);
           setRecipes(recipesData);
       };
       fetchData();
   }, []);
   ```

3. **Update component logic to handle async data**

## Database Management

### Reset and reseed database:
```bash
cd laravel-backend
php artisan migrate:fresh --seed
```

### Create new migration:
```bash
php artisan make:migration create_table_name
```

### Create new seeder:
```bash
php artisan make:seeder TableNameSeeder
```

## Testing the API

You can test the API endpoints using:

1. **Browser:** Visit `http://127.0.0.1:8000/api/categories`
2. **Postman:** Import the endpoints and test
3. **cURL:**
   ```bash
   curl http://127.0.0.1:8000/api/categories
   curl http://127.0.0.1:8000/api/recipes
   curl http://127.0.0.1:8000/api/articles
   ```

## Next Steps

1. **Update React components** to use the API service instead of static data
2. **Add loading states** for async operations
3. **Add error handling** for API calls
4. **Implement pagination** for large datasets
5. **Add authentication** if needed (Laravel Sanctum is already installed)
6. **Deploy** to production server

## Production Deployment

### Backend:
- Configure production database in `.env`
- Set `APP_ENV=production`
- Run `php artisan config:cache`
- Run `php artisan route:cache`
- Set up proper web server (Nginx/Apache)

### Frontend:
- Update API_BASE_URL in `src/services/api.js` to production URL
- Run `npm run build`
- Deploy `dist` folder to web server

## Troubleshooting

### CORS Issues:
If you encounter CORS errors, ensure:
1. Laravel backend is running
2. CORS middleware is properly configured in `bootstrap/app.php`
3. React app is making requests to the correct URL

### Database Issues:
```bash
# Reset database
php artisan migrate:fresh --seed

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

## Support

For issues or questions:
1. Check Laravel logs: `laravel-backend/storage/logs/laravel.log`
2. Check browser console for frontend errors
3. Verify API endpoints are accessible

---

**Conversion completed successfully!** 🎉

The Laravel backend is now running with all your recipe data, and the React frontend is ready to be integrated with the API.
