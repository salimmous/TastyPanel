<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Share;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    /**
     * Track a share
     */
    public function store(Request $request, Recipe $recipe): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'in:facebook,twitter,whatsapp,email,copy_link,pinterest'],
        ]);

        Share::create([
            'user_id' => auth()->id(),
            'recipe_id' => $recipe->id,
            'tenant_id' => $recipe->tenant_id,
            'platform' => $validated['platform'],
        ]);

        return response()->json([
            'message' => 'Share tracked successfully',
            'shares' => Share::where('recipe_id', $recipe->id)->count(),
        ]);
    }

    /**
     * Get share stats for a recipe
     */
    public function stats(Request $request, Recipe $recipe): JsonResponse
    {
        $total = Share::where('recipe_id', $recipe->id)->count();
        $byPlatform = Share::getSharesByPlatform($recipe->id);

        return response()->json([
            'total' => $total,
            'by_platform' => $byPlatform,
        ]);
    }

    /**
     * Get share URLs for a recipe
     */
    public function urls(Request $request, Recipe $recipe): JsonResponse
    {
        $url = route('recipes.show', $recipe);
        $title = $recipe->title;
        $description = $recipe->description ?? '';

        return response()->json([
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($url),
            'twitter' => 'https://twitter.com/intent/tweet?url='.urlencode($url).'&text='.urlencode($title),
            'whatsapp' => 'https://wa.me/?text='.urlencode("{$title} - {$url}"),
            'pinterest' => 'https://pinterest.com/pin/create/button/?url='.urlencode($url).'&description='.urlencode($title),
            'email' => 'mailto:?subject='.urlencode($title).'&body='.urlencode("{$title}\n\n{$description}\n\n{$url}"),
            'copy_link' => $url,
        ]);
    }
}
