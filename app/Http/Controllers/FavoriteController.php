<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Get user's favorites
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::where('user_id', auth()->id())
            ->with('recipe')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($favorites);
    }

    /**
     * Toggle favorite status
     */
    public function toggle(Request $request, Recipe $recipe): JsonResponse
    {
        $favorite = Favorite::where('user_id', auth()->id())
            ->where('recipe_id', $recipe->id)
            ->first();

        if ($favorite) {
            // Remove from favorites
            $favorite->delete();

            return response()->json([
                'favorited' => false,
                'favorites_count' => $recipe->fresh()->favorites_count,
                'message' => 'Recipe removed from favorites',
            ]);
        }

        // Add to favorites
        Favorite::create([
            'user_id' => auth()->id(),
            'recipe_id' => $recipe->id,
        ]);

        return response()->json([
            'favorited' => true,
            'favorites_count' => $recipe->fresh()->favorites_count,
            'message' => 'Recipe added to favorites',
        ]);
    }

    /**
     * Check if recipe is favorited by user
     */
    public function check(Request $request, Recipe $recipe): JsonResponse
    {
        $favorited = Favorite::where('user_id', auth()->id())
            ->where('recipe_id', $recipe->id)
            ->exists();

        return response()->json([
            'favorited' => $favorited,
        ]);
    }
}
