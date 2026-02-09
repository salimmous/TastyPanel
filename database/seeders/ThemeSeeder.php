<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        Theme::updateOrCreate(
            ['key' => 'food'],
            [
                'name' => 'Food Studio',
                'view' => 'themes.food.home',
                'description' => 'Warm editorial layout for restaurants, menus, and culinary brands.',
                'category' => 'Food & Beverage',
                'tags' => ['restaurant', 'menu', 'chef'],
                'author' => 'TastyPanel',
                'version' => '1.0.0',
                'is_featured' => true,
                'is_marketplace' => true,
                'is_active' => true,
            ]
        );

        Theme::updateOrCreate(
            ['key' => 'business'],
            [
                'name' => 'Business Stack',
                'view' => 'themes.business.home',
                'description' => 'Modern SaaS template for agencies and business niches.',
                'category' => 'Business',
                'tags' => ['agency', 'saas', 'startup'],
                'author' => 'TastyPanel',
                'version' => '1.0.0',
                'is_featured' => false,
                'is_marketplace' => true,
                'is_active' => true,
            ]
        );
    }
}
