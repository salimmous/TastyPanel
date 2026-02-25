<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class SeoHelper
{
    /**
     * Generate meta tags for a recipe
     */
    public static function recipeMetaTags(object $recipe): array
    {
        return [
            'title' => $recipe->seo_title ?? $recipe->title.' | Recipe',
            'description' => $recipe->seo_description ?? Str::limit(strip_tags($recipe->description), 160),
            'keywords' => $recipe->keywords ?? '',
            'og:title' => $recipe->title,
            'og:description' => Str::limit(strip_tags($recipe->description), 200),
            'og:image' => $recipe->image ?? '',
            'og:type' => 'article',
            'og:url' => url("/recipe/{$recipe->slug}"),
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $recipe->title,
            'twitter:description' => Str::limit(strip_tags($recipe->description), 200),
            'twitter:image' => $recipe->image ?? '',
        ];
    }

    /**
     * Generate meta tags for an article
     */
    public static function articleMetaTags(object $article): array
    {
        return [
            'title' => $article->seo_title ?? $article->title,
            'description' => $article->seo_description ?? Str::limit(strip_tags($article->content), 160),
            'keywords' => $article->keywords ?? '',
            'og:title' => $article->title,
            'og:description' => Str::limit(strip_tags($article->content), 200),
            'og:image' => $article->featured_image ?? '',
            'og:type' => 'article',
            'og:url' => url("/article/{$article->slug}"),
            'twitter:card' => 'summary_large_image',
        ];
    }

    /**
     * Generate Schema.org markup for recipe
     */
    public static function recipeSchema(object $recipe): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $recipe->title,
            'image' => $recipe->image,
            'author' => [
                '@type' => 'Person',
                'name' => $recipe->author ?? 'Admin',
            ],
            'datePublished' => $recipe->published_at ?? $recipe->created_at,
            'description' => strip_tags($recipe->description),
            'recipeIngredient' => is_string($recipe->ingredients) ? json_decode($recipe->ingredients, true) : $recipe->ingredients,
            'recipeInstructions' => strip_tags($recipe->instructions ?? ''),
            'cookTime' => isset($recipe->cook_time) ? "PT{$recipe->cook_time}M" : null,
            'prepTime' => isset($recipe->prep_time) ? "PT{$recipe->prep_time}M" : null,
            'totalTime' => isset($recipe->total_time) ? "PT{$recipe->total_time}M" : null,
            'recipeYield' => $recipe->servings ?? null,
            'recipeCategory' => $recipe->category_name ?? null,
            'keywords' => $recipe->keywords ?? '',
            'nutrition' => isset($recipe->calories) ? [
                '@type' => 'NutritionInformation',
                'calories' => $recipe->calories.' calories',
            ] : null,
        ];
    }

    /**
     * Generate Schema.org markup for article
     */
    public static function articleSchema(object $article): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'image' => $article->featured_image ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $article->author ?? 'Admin',
            ],
            'datePublished' => $article->published_at ?? $article->created_at,
            'dateModified' => $article->updated_at,
            'description' => Str::limit(strip_tags($article->content), 200),
        ];
    }
}
