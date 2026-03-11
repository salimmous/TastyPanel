<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\TenantContext;
use App\Models\Recipe;
use App\Models\Category;

class UpdateRecipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => 'sometimes',
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes',
            'description' => 'sometimes',
            'image' => 'sometimes',
            'prep_time' => 'sometimes',
            'cook_time' => 'sometimes',
            'servings' => 'sometimes|integer',
            'ingredients' => 'sometimes|array',
            'instructions' => 'sometimes|array',
            'nutrition' => 'nullable|array',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tenantId = TenantContext::id();
            $environment = TenantContext::environment();
            // Try both 'recipe' (default for apiResource) and 'id' as parameter names
            $recipeId = $this->route('recipe') ?? $this->route('id');

            if ($tenantId && $this->filled('slug')) {
                $exists = Recipe::where('tenant_id', $tenantId)
                    ->where('environment', $environment)
                    ->where('slug', $this->slug)
                    ->where('id', '!=', $recipeId)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('slug', 'Slug already exists.');
                }
            }

            if ($tenantId && $this->filled('category_id')) {
                $category = Category::where('environment', $environment)->find($this->category_id);
                if (!$category) {
                    $validator->errors()->add('category_id', 'Category does not belong to current environment.');
                } elseif ($category->tenant_id && $category->tenant_id !== $tenantId) {
                    $validator->errors()->add('category_id', 'Category does not belong to current tenant.');
                }
            }
        });
    }
}
