<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ThemeSeeder::class,
            TenantSeeder::class,
            AdminUserSeeder::class,
            CategorySeeder::class,
            RecipeSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
