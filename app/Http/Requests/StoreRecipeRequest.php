<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\TenantContext;
use App\Models\Recipe;
use App\Models\Category;

class StoreRecipeRequest extends FormRequest
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
            'slug' => 'required',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required',
            'description' => 'required',
            'image' => 'required',
            'prep_time' => 'required',
            'cook_time' => 'required',
            'servings' => 'required|integer',
            'ingredients' => 'required|array',
            'instructions' => 'required|array',
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

            if ($tenantId && $this->filled('slug')) {
                $exists = Recipe::where('tenant_id', $tenantId)
                    ->where('environment', $environment)
                    ->where('slug', $this->slug)
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
