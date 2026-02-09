<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Tenant;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $theme = Theme::where('key', 'food')->first();

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-food'],
            [
                'name' => 'Demo Food Studio',
                'theme_id' => $theme?->id,
                'status' => 'active',
            ]
        );

        $tenant->settings()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'environment' => 'production'],
            [
                'data' => [
                    'brand_name' => 'Demo Food Studio',
                    'tagline' => 'Signature Food Studio',
                    'primary_color' => '#1f2937',
                    'secondary_color' => '#f59e0b',
                    'accent_color' => '#f97316',
                    'hero_title' => 'Crafted menus for bold culinary brands.',
                    'hero_subtitle' => 'Launch premium food concepts, publish recipes, and deliver a curated dining experience on every domain.',
                ],
            ]
        );

        Domain::updateOrCreate(
            ['hostname' => 'demo.test'],
            [
                'tenant_id' => $tenant->id,
                'is_primary' => true,
                'status' => 'active',
                'environment' => 'production',
            ]
        );
    }
}
