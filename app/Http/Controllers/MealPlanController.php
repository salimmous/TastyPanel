<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Services\MealPlanningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MealPlanController extends Controller
{
    public function __construct(
        protected MealPlanningService $service
    ) {
    }

    /**
     * List user's meal plans
     */
    public function index(Request $request)
    {
        $tenantId = $request->get('tenant_id');

        $plans = MealPlan::where('tenant_id', $tenantId)
            ->where('user_id', Auth::id())
            ->withCount('items')
            ->orderByDesc('start_date')
            ->paginate($request->get('per_page', 10));

        return response()->json($plans);
    }

    /**
     * Create meal plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ]);

        $plan = MealPlan::create([
            'tenant_id' => $validated['tenant_id'],
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'data' => $plan,
            'message' => 'Meal plan created',
        ], 201);
    }

    /**
     * Show meal plan with calendar view
     */
    public function show(MealPlan $mealPlan)
    {
        $this->authorizeOwner($mealPlan);

        return response()->json([
            'data' => $mealPlan,
            'calendar' => $this->service->getCalendarView($mealPlan),
        ]);
    }

    /**
     * Update meal plan
     */
    public function update(Request $request, MealPlan $mealPlan)
    {
        $this->authorizeOwner($mealPlan);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $mealPlan->update($validated);

        return response()->json([
            'data' => $mealPlan->fresh(),
            'message' => 'Meal plan updated',
        ]);
    }

    /**
     * Delete meal plan
     */
    public function destroy(MealPlan $mealPlan)
    {
        $this->authorizeOwner($mealPlan);

        $mealPlan->delete();

        return response()->json([
            'message' => 'Meal plan deleted',
        ]);
    }

    /**
     * Add item to meal plan
     */
    public function addItem(Request $request, MealPlan $mealPlan)
    {
        $this->authorizeOwner($mealPlan);

        $validated = $request->validate([
            'recipe_id' => ['required', 'exists:recipes,id'],
            'planned_date' => ['required', 'date'],
            'meal_type' => ['required', 'string', 'in:breakfast,lunch,dinner,snack'],
            'servings' => ['integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = $mealPlan->items()->create($validated);

        return response()->json([
            'data' => $item->load('recipe:id,title,image'),
            'message' => 'Item added',
        ], 201);
    }

    /**
     * Update item
     */
    public function updateItem(Request $request, MealPlanItem $item)
    {
        $this->authorizeOwner($item->mealPlan);

        $validated = $request->validate([
            'planned_date' => ['sometimes', 'date'],
            'meal_type' => ['sometimes', 'string', 'in:breakfast,lunch,dinner,snack'],
            'servings' => ['integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'is_completed' => ['boolean'],
        ]);

        $item->update($validated);

        return response()->json([
            'data' => $item->fresh(),
            'message' => 'Item updated',
        ]);
    }

    /**
     * Remove item
     */
    public function removeItem(MealPlanItem $item)
    {
        $this->authorizeOwner($item->mealPlan);

        $item->delete();

        return response()->json([
            'message' => 'Item removed',
        ]);
    }

    /**
     * Generate shopping list from meal plan
     */
    public function generateShoppingList(Request $request, MealPlan $mealPlan)
    {
        $this->authorizeOwner($mealPlan);

        $name = $request->input('name');
        $shoppingList = $this->service->generateShoppingList($mealPlan, $name);

        return response()->json([
            'data' => $shoppingList->load('items'),
            'message' => 'Shopping list generated',
        ], 201);
    }

    /**
     * Get recipe suggestions
     */
    public function suggestions(Request $request, MealPlan $mealPlan)
    {
        $this->authorizeOwner($mealPlan);

        $mealType = $request->get('meal_type', 'dinner');
        $limit = min(10, (int) $request->get('limit', 5));

        return response()->json([
            'data' => $this->service->suggestRecipes($mealPlan, $mealType, $limit),
        ]);
    }

    protected function authorizeOwner(MealPlan $mealPlan): void
    {
        if ($mealPlan->user_id !== Auth::id()) {
            abort(403, 'You do not own this meal plan');
        }
    }
}
