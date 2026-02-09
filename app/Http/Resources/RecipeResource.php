<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'prep_time' => $this->prep_time,
            'cook_time' => $this->cook_time,
            'total_time' => ($this->prep_time ?? 0) + ($this->cook_time ?? 0),
            'servings' => $this->servings,
            'calories' => $this->calories,
            'ingredients' => is_string($this->ingredients)
                ? json_decode($this->ingredients, true)
                : $this->ingredients,
            'instructions' => $this->instructions,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
