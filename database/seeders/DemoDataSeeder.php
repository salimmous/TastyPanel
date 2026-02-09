<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run all demo seeders
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding demo data...');

        $this->call([
            CategorySeeder::class,
            RecipeSeeder::class,
        ]);

        $this->command->info('✅ Demo data seeded successfully!');
    }
}
