<?php

namespace App\Http\Controllers;

use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShoppingListController extends Controller
{
    /**
     * List user's shopping lists
     */
    public function index(Request $request)
    {
        $tenantId = $request->get('tenant_id');

        $lists = ShoppingList::where('tenant_id', $tenantId)
            ->where('user_id', Auth::id())
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 10));

        return response()->json($lists);
    }

    /**
     * Create shopping list
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:100'],
            'shop_date' => ['nullable', 'date'],
        ]);

        $list = ShoppingList::create([
            'tenant_id' => $validated['tenant_id'],
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'shop_date' => $validated['shop_date'] ?? null,
        ]);

        return response()->json([
            'data' => $list,
            'message' => 'Shopping list created',
        ], 201);
    }

    /**
     * Show shopping list with items
     */
    public function show(ShoppingList $shoppingList)
    {
        $this->authorizeOwner($shoppingList);

        return response()->json([
            'data' => $shoppingList->load('items'),
            'grouped' => $shoppingList->getGroupedItems(),
            'progress' => $shoppingList->getProgress(),
        ]);
    }

    /**
     * Update shopping list
     */
    public function update(Request $request, ShoppingList $shoppingList)
    {
        $this->authorizeOwner($shoppingList);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'shop_date' => ['nullable', 'date'],
        ]);

        $shoppingList->update($validated);

        return response()->json([
            'data' => $shoppingList->fresh(),
            'message' => 'Shopping list updated',
        ]);
    }

    /**
     * Delete shopping list
     */
    public function destroy(ShoppingList $shoppingList)
    {
        $this->authorizeOwner($shoppingList);

        $shoppingList->delete();

        return response()->json([
            'message' => 'Shopping list deleted',
        ]);
    }

    /**
     * Add item to list
     */
    public function addItem(Request $request, ShoppingList $shoppingList)
    {
        $this->authorizeOwner($shoppingList);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'quantity' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = $shoppingList->addItem($validated);

        return response()->json([
            'data' => $item,
            'message' => 'Item added',
        ], 201);
    }

    /**
     * Update item
     */
    public function updateItem(Request $request, ShoppingListItem $item)
    {
        $this->authorizeOwner($item->shoppingList);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'quantity' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $item->update($validated);

        return response()->json([
            'data' => $item->fresh(),
            'message' => 'Item updated',
        ]);
    }

    /**
     * Toggle item checked
     */
    public function toggleItem(ShoppingListItem $item)
    {
        $this->authorizeOwner($item->shoppingList);

        $isChecked = $item->toggle();

        return response()->json([
            'is_checked' => $isChecked,
            'progress' => $item->shoppingList->getProgress(),
        ]);
    }

    /**
     * Remove item
     */
    public function removeItem(ShoppingListItem $item)
    {
        $this->authorizeOwner($item->shoppingList);
        $list = $item->shoppingList;

        $item->delete();
        $list->updateCounts();

        return response()->json([
            'message' => 'Item removed',
        ]);
    }

    /**
     * Bulk check/uncheck items
     */
    public function bulkToggle(Request $request, ShoppingList $shoppingList)
    {
        $this->authorizeOwner($shoppingList);

        $validated = $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['integer'],
            'checked' => ['required', 'boolean'],
        ]);

        $shoppingList->items()
            ->whereIn('id', $validated['item_ids'])
            ->update(['is_checked' => $validated['checked']]);

        $shoppingList->updateCounts();

        return response()->json([
            'message' => count($validated['item_ids']) . ' items updated',
            'progress' => $shoppingList->getProgress(),
        ]);
    }

    /**
     * Clear checked items
     */
    public function clearChecked(ShoppingList $shoppingList)
    {
        $this->authorizeOwner($shoppingList);

        $count = $shoppingList->items()->where('is_checked', true)->delete();
        $shoppingList->updateCounts();

        return response()->json([
            'message' => "{$count} items cleared",
        ]);
    }

    protected function authorizeOwner(ShoppingList $shoppingList): void
    {
        if ($shoppingList->user_id !== Auth::id()) {
            abort(403, 'You do not own this shopping list');
        }
    }
}
