<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = Tenant::query()->value('id');
        $articles = [
            [
                'tenant_id' => $tenantId,
                'slug' => 'pecan-pie-cheesecake',
                'title' => 'Pecan Pie Cheesecake',
                'description' => 'This Pecan Pie Cheesecake combines the richness of cheesecake with the sweetness and nutty topping of pecan pie—a perfect holiday dessert.',
                'image' => 'https://images.unsplash.com/photo-1509456592530-5d38e33f3fdd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            ],
            [
                'tenant_id' => $tenantId,
                'slug' => 'fruity-marshmallow-fudge',
                'title' => 'Fruity Marshmallow Fudge',
                'description' => 'A colorful, fruity marshmallow fudge with creamy white chocolate, perfect for parties or a sweet snack.',
                'image' => 'https://images.unsplash.com/photo-1621236378699-8597fcfcd284?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            ],
            [
                'tenant_id' => $tenantId,
                'slug' => 'lasagna-soup',
                'title' => 'Lasagna Soup',
                'description' => 'Lasagna Soup combines the flavors of classic lasagna in a warm, hearty soup. It\'s topped with a blend of ricotta, mozzarella, and Parmesan for a rich, comforting meal.',
                'image' => 'https://images.unsplash.com/photo-1547592166-23acbe346499?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
            ],
        ];

        foreach ($articles as $article) {
            Article::create($article);
        }
    }
}
