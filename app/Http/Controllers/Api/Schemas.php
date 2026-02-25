<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     title="TastyPanel Platform API",
 *     version="1.0.0",
 *     description="RESTful API for TastyPanel multi-tenant recipe platform",
 *
 *     @OA\Contact(email="admin@tastypanel.site", name="TastyPanel Support")
 * )
 *
 * @OA\Server(url="http://localhost:8000", description="Local Development")
 * @OA\Server(url="https://api.tastypanel.site", description="Production")
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * Schema definitions for API documentation
 */

/**
 * @OA\Schema(
 *     schema="Recipe",
 *     type="object",
 *     title="Recipe",
 *     description="Recipe object with all details",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Chocolate Chip Cookies"),
 *     @OA\Property(property="slug", type="string", example="chocolate-chip-cookies"),
 *     @OA\Property(property="description", type="string", example="Delicious homemade cookies"),
 *     @OA\Property(property="image", type="string", example="https://example.com/image.jpg"),
 *     @OA\Property(property="prep_time", type="integer", example=15, description="Preparation time in minutes"),
 *     @OA\Property(property="cook_time", type="integer", example=12, description="Cooking time in minutes"),
 *     @OA\Property(property="total_time", type="integer", example=27),
 *     @OA\Property(property="servings", type="integer", example=24),
 *     @OA\Property(property="calories", type="integer", example=150, nullable=true),
 *     @OA\Property(
 *         property="ingredients",
 *         type="array",
 *
 *         @OA\Items(type="string", example="2 cups all-purpose flour")
 *     ),
 *
 *     @OA\Property(property="instructions", type="string"),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="slug", type="string")
 *     ),
 *     @OA\Property(property="published_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="RecipeInput",
 *     type="object",
 *     title="Recipe Input",
 *     required={"title", "description"},
 *
 *     @OA\Property(property="title", type="string", maxLength=255, example="New Recipe"),
 *     @OA\Property(property="description", type="string", example="Recipe description"),
 *     @OA\Property(property="image", type="string", format="uri", nullable=true),
 *     @OA\Property(property="prep_time", type="integer", minimum=0, nullable=true),
 *     @OA\Property(property="cook_time", type="integer", minimum=0, nullable=true),
 *     @OA\Property(property="servings", type="integer", minimum=1, nullable=true),
 *     @OA\Property(property="calories", type="integer", nullable=true),
 *     @OA\Property(
 *         property="ingredients",
 *         type="array",
 *
 *         @OA\Items(type="string")
 *     ),
 *
 *     @OA\Property(property="instructions", type="string"),
 *     @OA\Property(property="category_id", type="integer", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Desserts"),
 *     @OA\Property(property="slug", type="string", example="desserts"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="recipes_count", type="integer", example=25)
 * )
 *
 * @OA\Schema(
 *     schema="Article",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="featured_image", type="string", nullable=true),
 *     @OA\Property(property="published_at", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Tenant",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="domain", type="string"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="total", type="integer", example=100),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="last_page", type="integer", example=7),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=15)
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error message"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\Property(
 *             property="field_name",
 *             type="array",
 *
 *             @OA\Items(type="string", example="The field is required.")
 *         )
 *     )
 * )
 */
class Schemas
{
    // This class is only for schema definitions
}
