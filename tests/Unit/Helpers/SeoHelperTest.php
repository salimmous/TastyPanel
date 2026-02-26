<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SeoHelper;
use Tests\TestCase;
use Illuminate\Support\Str;

class SeoHelperTest extends TestCase
{
    /**
     * Test recipeMetaTags with full SEO data provided.
     */
    public function test_recipe_meta_tags_with_full_data(): void
    {
        $recipe = (object) [
            'title' => 'Delicious Pasta',
            'description' => 'A great pasta recipe.',
            'seo_title' => 'Best Pasta Recipe Ever',
            'seo_description' => 'Custom SEO description for the best pasta recipe.',
            'keywords' => 'pasta, italian, dinner',
            'image' => 'https://example.com/pasta.jpg',
            'slug' => 'delicious-pasta',
        ];

        $tags = SeoHelper::recipeMetaTags($recipe);

        $this->assertEquals('Best Pasta Recipe Ever', $tags['title']);
        $this->assertEquals('Custom SEO description for the best pasta recipe.', $tags['description']);
        $this->assertEquals('pasta, italian, dinner', $tags['keywords']);
        $this->assertEquals('Delicious Pasta', $tags['og:title']);
        $this->assertEquals('A great pasta recipe.', $tags['og:description']);
        $this->assertEquals('https://example.com/pasta.jpg', $tags['og:image']);
        $this->assertEquals('article', $tags['og:type']);
        $this->assertEquals(url('/recipe/delicious-pasta'), $tags['og:url']);
        $this->assertEquals('summary_large_image', $tags['twitter:card']);
        $this->assertEquals('Delicious Pasta', $tags['twitter:title']);
        $this->assertEquals('A great pasta recipe.', $tags['twitter:description']);
        $this->assertEquals('https://example.com/pasta.jpg', $tags['twitter:image']);
    }

    /**
     * Test recipeMetaTags with default values when SEO fields are missing.
     */
    public function test_recipe_meta_tags_with_defaults(): void
    {
        $recipe = (object) [
            'title' => 'Basic Salad',
            'description' => 'Simple green salad.',
            'slug' => 'basic-salad',
            // Missing: seo_title, seo_description, keywords, image
        ];

        $tags = SeoHelper::recipeMetaTags($recipe);

        $this->assertEquals('Basic Salad | Recipe', $tags['title']);
        $this->assertEquals('Simple green salad.', $tags['description']);
        $this->assertEquals('', $tags['keywords']);
        $this->assertEquals('', $tags['og:image']);
        $this->assertEquals(url('/recipe/basic-salad'), $tags['og:url']);
        $this->assertEquals('', $tags['twitter:image']);
    }

    /**
     * Test that HTML tags are stripped and description is limited.
     */
    public function test_recipe_meta_tags_strips_html_and_limits_length(): void
    {
        $longDescription = 'This is a <b>very long</b> description that contains <a href="#">HTML tags</a> and should be stripped and truncated to a certain length to ensure it fits within the recommended meta tag limits. ' . str_repeat('Additional text to make it longer. ', 10);

        $recipe = (object) [
            'title' => 'Long Description Recipe',
            'description' => $longDescription,
            'slug' => 'long-description',
        ];

        $tags = SeoHelper::recipeMetaTags($recipe);

        $stripped = strip_tags($longDescription);

        $this->assertEquals(Str::limit($stripped, 160), $tags['description']);
        $this->assertEquals(Str::limit($stripped, 200), $tags['og:description']);
        $this->assertEquals(Str::limit($stripped, 200), $tags['twitter:description']);

        $this->assertStringNotContainsString('<b>', $tags['description']);
        $this->assertStringNotContainsString('</a>', $tags['description']);
    }

    /**
     * Test articleMetaTags with full SEO data provided.
     */
    public function test_article_meta_tags_with_full_data(): void
    {
        $article = (object) [
            'title' => 'Awesome Article',
            'content' => 'This is the content of the article.',
            'seo_title' => 'Best Article Ever',
            'seo_description' => 'Custom SEO description for the article.',
            'keywords' => 'article, news, tech',
            'featured_image' => 'https://example.com/article.jpg',
            'slug' => 'awesome-article',
        ];

        $tags = SeoHelper::articleMetaTags($article);

        $this->assertEquals('Best Article Ever', $tags['title']);
        $this->assertEquals('Custom SEO description for the article.', $tags['description']);
        $this->assertEquals('article, news, tech', $tags['keywords']);
        $this->assertEquals('Awesome Article', $tags['og:title']);
        $this->assertEquals('This is the content of the article.', $tags['og:description']);
        $this->assertEquals('https://example.com/article.jpg', $tags['og:image']);
        $this->assertEquals('article', $tags['og:type']);
        $this->assertEquals(url('/article/awesome-article'), $tags['og:url']);
        $this->assertEquals('summary_large_image', $tags['twitter:card']);
    }

    /**
     * Test articleMetaTags with default values when SEO fields are missing.
     */
    public function test_article_meta_tags_with_defaults(): void
    {
        $article = (object) [
            'title' => 'Simple Article',
            'content' => 'Just some content here.',
            'slug' => 'simple-article',
            // Missing: seo_title, seo_description, keywords, featured_image
        ];

        $tags = SeoHelper::articleMetaTags($article);

        $this->assertEquals('Simple Article', $tags['title']);
        $this->assertEquals('Just some content here.', $tags['description']);
        $this->assertEquals('', $tags['keywords']);
        $this->assertEquals('Simple Article', $tags['og:title']);
        $this->assertEquals('Just some content here.', $tags['og:description']);
        $this->assertEquals('', $tags['og:image']);
        $this->assertEquals('article', $tags['og:type']);
        $this->assertEquals(url('/article/simple-article'), $tags['og:url']);
        $this->assertEquals('summary_large_image', $tags['twitter:card']);
    }

    /**
     * Test articleMetaTags strips HTML tags and limits description length.
     */
    public function test_article_meta_tags_strips_html_and_limits_length(): void
    {
        $longContent = 'This is a <b>very long</b> article content that contains <a href="#">HTML tags</a> and should be stripped and truncated to a certain length to ensure it fits within the recommended meta tag limits. ' . str_repeat('Additional text to make it longer. ', 10);

        $article = (object) [
            'title' => 'Long Article',
            'content' => $longContent,
            'slug' => 'long-article',
        ];

        $tags = SeoHelper::articleMetaTags($article);

        $stripped = strip_tags($longContent);

        $this->assertEquals(Str::limit($stripped, 160), $tags['description']);
        $this->assertEquals(Str::limit($stripped, 200), $tags['og:description']);

        $this->assertStringNotContainsString('<b>', $tags['description']);
        $this->assertStringNotContainsString('</a>', $tags['description']);
        $this->assertStringNotContainsString('<b>', $tags['og:description']);
    }
}
