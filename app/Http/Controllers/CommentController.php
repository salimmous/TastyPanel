<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Get comments for a recipe
     */
    public function index(Request $request, Recipe $recipe): JsonResponse
    {
        $comments = Comment::where('recipe_id', $recipe->id)
            ->approved()
            ->topLevel()
            ->with(['replies.user'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($comments);
    }

    /**
     * Store a new comment
     */
    public function store(Request $request, Recipe $recipe): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        // Auto-approve comments (or set to false for moderation)
        $comment = Comment::create([
            'user_id' => auth()->id(),
            'recipe_id' => $recipe->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'tenant_id' => auth()->user()->tenant_id,
            'content' => $validated['content'],
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        return response()->json([
            'comment' => $comment->load(['user', 'replies']),
            'message' => 'Comment posted successfully',
        ], 201);
    }

    /**
     * Update a comment
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        if (!$comment->canEdit(auth()->user())) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return response()->json([
            'comment' => $comment->fresh(['user', 'replies']),
            'message' => 'Comment updated successfully',
        ]);
    }

    /**
     * Delete a comment
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        if (!$comment->canDelete(auth()->user())) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
