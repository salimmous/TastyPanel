<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Get ratings for a recipe
     */
    public function index(Request $request, Recipe $recipe): JsonResponse
    {
        $ratings = Rating::where('recipe_id', $recipe->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        $distribution = Rating::getDistribution($recipe->id);

        return response()->json([
            'ratings' => $ratings,
            'distribution' => $distribution,
            'average' => $recipe->average_rating,
            'total' => $recipe->rating_count,
        ]);
    }

    /**
     * Store or update rating
     */
    public function store(Request $request, Recipe $recipe): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        $rating = Rating::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'recipe_id' => $recipe->id,
            ],
            [
                'rating' => $validated['rating'],
                'review' => $validated['review'] ?? null,
                'tenant_id' => auth()->user()->tenant_id,
            ]
        );

        return response()->json([
            'rating' => $rating->load('user:id,name'),
            'recipe' => [
                'average_rating' => $recipe->fresh()->average_rating,
                'rating_count' => $recipe->fresh()->rating_count,
            ],
            'message' => 'Rating submitted successfully',
        ]);
    }

    /**
     * Get user's rating for a recipe
     */
    public function show(Request $request, Recipe $recipe): JsonResponse
    {
        $rating = Rating::where('user_id', auth()->id())
            ->where('recipe_id', $recipe->id)
            ->first();

        return response()->json([
            'rating' => $rating,
        ]);
    }

    /**
     * Delete rating
     */
    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        $rating = Rating::where('user_id', auth()->id())
            ->where('recipe_id', $recipe->id)
            ->first();

        if (! $rating) {
            return response()->json([
                'message' => 'Rating not found',
            ], 404);
        }

        $rating->delete();

        return response()->json([
            'message' => 'Rating deleted successfully',
            'recipe' => [
                'average_rating' => $recipe->fresh()->average_rating,
                'rating_count' => $recipe->fresh()->rating_count,
            ],
        ]);
    }
}
