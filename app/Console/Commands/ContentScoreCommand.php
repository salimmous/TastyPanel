<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Article;
use App\Models\Recipe;
use App\Services\ContentScoringService;

class ContentScoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:score {--tenant=} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate content scores for articles/recipes';

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\ContentScoringService  $scoring
     * @return void
     */
    public function __construct(private ContentScoringService $scoring)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $type = $this->option('type');

        $articleQuery = Article::query();
        $recipeQuery = Recipe::query();

        if ($tenantId) {
            $articleQuery->where('tenant_id', $tenantId);
            $recipeQuery->where('tenant_id', $tenantId);
        }

        $count = 0;

        if (!$type || $type === 'articles') {
            $articleQuery->chunkById(100, function ($articles) use (&$count) {
                foreach ($articles as $article) {
                    $this->scoreArticle($article);
                    $count++;
                }
            });
        }

        if (!$type || $type === 'recipes') {
            $recipeQuery->chunkById(100, function ($recipes) use (&$count) {
                foreach ($recipes as $recipe) {
                    $this->scoreRecipe($recipe);
                    $count++;
                }
            });
        }

        $this->info("Scored {$count} items.");

        return 0;
    }

    /**
     * Score a single article.
     *
     * @param  \App\Models\Article  $article
     * @return void
     */
    private function scoreArticle(Article $article)
    {
        $score = $this->scoring->score($article->title ?? '', $article->description ?? '');
        $article->fill($score);
        $article->save();
    }

    /**
     * Score a single recipe.
     *
     * @param  \App\Models\Recipe  $recipe
     * @return void
     */
    private function scoreRecipe(Recipe $recipe)
    {
        $parts = [];
        if (!empty($recipe->description)) {
            $parts[] = $recipe->description;
        }
        if (is_array($recipe->ingredients)) {
            $parts[] = implode(' ', $recipe->ingredients);
        }
        if (is_array($recipe->instructions)) {
            $parts[] = implode(' ', array_map(function ($item) {
                if (is_string($item)) {
                    return $item;
                }
                if (is_array($item)) {
                    return implode(' ', $item);
                }
                return '';
            }, $recipe->instructions));
        }

        $score = $this->scoring->score($recipe->title ?? '', implode("\n", $parts));
        $recipe->fill($score);
        $recipe->save();
    }
}
