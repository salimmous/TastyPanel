# Recipe Website Architecture

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT SIDE                              │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    React Frontend                         │  │
│  │                  (Port: 5173/Vite)                       │  │
│  │                                                           │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐        │  │
│  │  │   Pages    │  │ Components │  │   Assets   │        │  │
│  │  ├────────────┤  ├────────────┤  ├────────────┤        │  │
│  │  │ Home       │  │ Header     │  │ Images     │        │  │
│  │  │ Category   │  │ Footer     │  │ Styles     │        │  │
│  │  │ Recipe     │  │ RecipeCard │  │            │        │  │
│  │  │ About      │  │ CategoryCard│  │            │        │  │
│  │  │ Contact    │  │ SearchModal│  │            │        │  │
│  │  └────────────┘  └────────────┘  └────────────┘        │  │
│  │                                                           │  │
│  │  ┌────────────────────────────────────────────────────┐ │  │
│  │  │           API Service Layer (api.js)               │ │  │
│  │  │                                                     │ │  │
│  │  │  • getCategories()                                 │ │  │
│  │  │  • getRecipes()                                    │ │  │
│  │  │  • searchRecipes()                                 │ │  │
│  │  │  • getRecipesByCategory()                          │ │  │
│  │  └────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────┘  │
│                              │                                  │
│                              │ HTTP/JSON                        │
│                              ▼                                  │
└─────────────────────────────────────────────────────────────────┘

                               │
                               │
                               ▼

┌─────────────────────────────────────────────────────────────────┐
│                         SERVER SIDE                              │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              Laravel Backend API                          │  │
│  │              (Port: 8000)                                 │  │
│  │                                                           │  │
│  │  ┌────────────────────────────────────────────────────┐ │  │
│  │  │                  Routes (api.php)                   │ │  │
│  │  │                                                     │ │  │
│  │  │  GET  /api/categories                              │ │  │
│  │  │  GET  /api/categories/{slug}                       │ │  │
│  │  │  GET  /api/recipes                                 │ │  │
│  │  │  GET  /api/recipes?category={slug}                 │ │  │
│  │  │  GET  /api/recipes?search={term}                   │ │  │
│  │  │  GET  /api/recipes/{slug}                          │ │  │
│  │  │  GET  /api/articles                                │ │  │
│  │  └────────────────────────────────────────────────────┘ │  │
│  │                              │                            │  │
│  │                              ▼                            │  │
│  │  ┌────────────────────────────────────────────────────┐ │  │
│  │  │              API Controllers                        │ │  │
│  │  │                                                     │ │  │
│  │  │  ┌──────────────┐  ┌──────────────┐  ┌──────────┐│ │  │
│  │  │  │  Category    │  │   Recipe     │  │ Article  ││ │  │
│  │  │  │  Controller  │  │  Controller  │  │Controller││ │  │
│  │  │  └──────────────┘  └──────────────┘  └──────────┘│ │  │
│  │  └────────────────────────────────────────────────────┘ │  │
│  │                              │                            │  │
│  │                              ▼                            │  │
│  │  ┌────────────────────────────────────────────────────┐ │  │
│  │  │              Eloquent Models                        │ │  │
│  │  │                                                     │ │  │
│  │  │  ┌──────────┐  ┌──────────┐  ┌──────────┐        │ │  │
│  │  │  │ Category │  │  Recipe  │  │ Article  │        │ │  │
│  │  │  │          │  │          │  │          │        │ │  │
│  │  │  │ hasMany  │◄─┤belongsTo │  │          │        │ │  │
│  │  │  │ recipes  │  │ category │  │          │        │ │  │
│  │  │  └──────────┘  └──────────┘  └──────────┘        │ │  │
│  │  └────────────────────────────────────────────────────┘ │  │
│  │                              │                            │  │
│  │                              ▼                            │  │
│  │  ┌────────────────────────────────────────────────────┐ │  │
│  │  │              Database (SQLite)                      │ │  │
│  │  │                                                     │ │  │
│  │  │  ┌──────────────┐  ┌──────────────┐  ┌──────────┐│ │  │
│  │  │  │ categories   │  │   recipes    │  │ articles ││ │  │
│  │  │  ├──────────────┤  ├──────────────┤  ├──────────┤│ │  │
│  │  │  │ id           │  │ id           │  │ id       ││ │  │
│  │  │  │ slug         │  │ slug         │  │ slug     ││ │  │
│  │  │  │ name         │  │ category_id  │  │ title    ││ │  │
│  │  │  │ image        │  │ title        │  │ desc...  ││ │  │
│  │  │  │ description  │  │ description  │  │ image    ││ │  │
│  │  │  │ timestamps   │  │ image        │  │ times... ││ │  │
│  │  │  │              │  │ prep_time    │  │          ││ │  │
│  │  │  │              │  │ cook_time    │  │          ││ │  │
│  │  │  │              │  │ servings     │  │          ││ │  │
│  │  │  │              │  │ ingredients  │  │          ││ │  │
│  │  │  │              │  │ instructions │  │          ││ │  │
│  │  │  │              │  │ nutrition    │  │          ││ │  │
│  │  │  │              │  │ timestamps   │  │          ││ │  │
│  │  │  └──────────────┘  └──────────────┘  └──────────┘│ │  │
│  │  │                                                     │ │  │
│  │  │  8 Categories  │  9 Recipes      │  3 Articles    │ │  │
│  │  └────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow

### 1. User Request Flow
```
User Action → React Component → API Service → HTTP Request → 
Laravel Route → Controller → Model → Database → 
Response (JSON) → React State → UI Update
```

### 2. Example: Fetching Recipes by Category
```
1. User clicks "Evening Meals" category
2. React: Category.jsx component loads
3. React: Calls api.getRecipesByCategory('evening-meals')
4. HTTP: GET /api/recipes?category=evening-meals
5. Laravel: Route matches RecipeController@index
6. Laravel: Controller filters recipes by category
7. Laravel: Eloquent queries database with relationship
8. Database: Returns recipes with category data
9. Laravel: Returns JSON response
10. React: Updates state with recipes
11. React: Renders RecipeCard components
12. User: Sees filtered recipes
```

## Technology Stack

### Frontend
- **Framework**: React 19.2.0
- **Build Tool**: Vite 7.2.4
- **Styling**: Tailwind CSS 4.1.18
- **Routing**: React Router DOM 7.10.1
- **Icons**: Lucide React 0.560.0

### Backend
- **Framework**: Laravel 12.x
- **Language**: PHP 8.4.10
- **Database**: SQLite
- **API**: RESTful with Laravel Sanctum
- **ORM**: Eloquent

## Key Features

### API Features
✅ RESTful endpoints
✅ Search functionality
✅ Category filtering
✅ Slug-based routing
✅ JSON responses
✅ CORS enabled
✅ Relationship loading (eager loading)
✅ Request validation

### Database Features
✅ Migrations for version control
✅ Seeders for sample data
✅ Foreign key constraints
✅ JSON field support
✅ Timestamps
✅ Soft deletes ready

### Frontend Features
✅ Component-based architecture
✅ API service layer
✅ Responsive design
✅ Search modal
✅ Category filtering
✅ Recipe details
✅ Contact form

## Development Workflow

```
┌─────────────────┐
│  Make Changes   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌──────────────────┐
│  Backend (PHP)  │     │  Frontend (JS)   │
├─────────────────┤     ├──────────────────┤
│ • Edit Models   │     │ • Edit Components│
│ • Edit Routes   │     │ • Edit Pages     │
│ • Edit Seeders  │     │ • Edit Styles    │
│ • Run Migrations│     │ • Update API     │
└────────┬────────┘     └────────┬─────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌──────────────────┐
│  Test API       │     │  Test UI         │
│  (curl/Postman) │     │  (Browser)       │
└────────┬────────┘     └────────┬─────────┘
         │                       │
         └───────────┬───────────┘
                     ▼
              ┌─────────────┐
              │   Deploy    │
              └─────────────┘
```

## File Organization

```
9bie/
├── laravel-backend/          # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   └── Models/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
│       └── api.php
│
├── src/                      # React App
│   ├── components/
│   ├── pages/
│   ├── services/
│   │   └── api.js
│   └── examples/
│
├── start.sh                  # Quick start script
├── README-LARAVEL.md         # Documentation
└── CONVERSION-SUMMARY.md     # This file
```

---

**Architecture Status**: ✅ Complete and Operational
