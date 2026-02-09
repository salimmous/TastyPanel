<?php

return [
    'recipes' => [
        'name' => 'Recipes Starter',
        'description' => 'Food-focused layout with recipe and category defaults.',
        'theme_key' => 'recipes',
        'settings' => [
            'brand_name' => 'Recipe Hub',
            'tagline' => 'Cook better, eat better.',
            'primary_color' => '#1f2937',
            'secondary_color' => '#f59e0b',
        ],
        'automation' => [
            'openai' => [
                'enabled' => false,
                'model' => 'gpt-4o-mini',
                'temperature' => 0.6,
                'max_tokens' => 900,
                'system_prompt' => 'Write friendly, practical recipes with clear steps.',
            ],
            'pipeline' => [
                'generate_title' => true,
                'generate_excerpt' => true,
                'generate_image' => true,
                'auto_tag' => true,
                'auto_category' => true,
                'auto_publish' => false,
            ],
        ],
    ],
    'travel' => [
        'name' => 'Travel Guide',
        'description' => 'Itineraries, guides, and destination highlights.',
        'theme_key' => 'travel',
        'settings' => [
            'brand_name' => 'Travel Atlas',
            'tagline' => 'Plan smarter, travel deeper.',
            'primary_color' => '#0f172a',
            'secondary_color' => '#38bdf8',
        ],
        'automation' => [
            'openai' => [
                'enabled' => false,
                'model' => 'gpt-4o-mini',
                'temperature' => 0.7,
                'max_tokens' => 1200,
                'system_prompt' => 'Write immersive travel guides with practical tips.',
            ],
            'pipeline' => [
                'generate_title' => true,
                'generate_excerpt' => true,
                'generate_image' => true,
                'auto_tag' => true,
                'auto_category' => true,
                'auto_publish' => false,
            ],
        ],
    ],
    'news' => [
        'name' => 'Newsroom',
        'description' => 'Fast publishing and editorial workflow defaults.',
        'theme_key' => 'news',
        'settings' => [
            'brand_name' => 'Daily Wire',
            'tagline' => 'Breaking stories, trusted summaries.',
            'primary_color' => '#111827',
            'secondary_color' => '#ef4444',
        ],
        'automation' => [
            'openai' => [
                'enabled' => false,
                'model' => 'gpt-4o-mini',
                'temperature' => 0.4,
                'max_tokens' => 1000,
                'system_prompt' => 'Write concise, factual news summaries.',
            ],
            'pipeline' => [
                'generate_title' => true,
                'generate_excerpt' => true,
                'generate_image' => false,
                'auto_tag' => true,
                'auto_category' => true,
                'auto_publish' => false,
            ],
        ],
    ],
];
