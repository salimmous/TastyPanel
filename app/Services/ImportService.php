<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Import;
use App\Models\Recipe;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use SimpleXMLElement;

class ImportService
{
    /**
     * Create a new import record
     */
    public function create(Tenant $tenant, UploadedFile $file, string $format, array $options = []): Import
    {
        $path = $file->store("imports/{$tenant->id}", 'local');

        return Import::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'format' => $format,
            'type' => $options['type'] ?? 'recipe',
            'status' => 'pending',
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'options' => $options,
        ]);
    }

    /**
     * Process CSV import
     */
    public function processCSV(Import $import): void
    {
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $csv = Reader::createFromPath(Storage::path($import->file_path), 'r');
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());
            $total = count($records);

            $import->update(['total_items' => $total]);

            $errors = [];
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;

            foreach ($records as $index => $record) {
                try {
                    $this->importRecipeFromCSV($import, $record);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 2, // +2 for header and 0-index
                        'error' => $e->getMessage(),
                        'data' => $record,
                    ];
                    $errorCount++;
                }

                $import->update(['processed_items' => $index + 1]);
            }

            $import->update([
                'status' => $errorCount > 0 ? 'completed_with_errors' : 'completed',
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'errors' => [['error' => $e->getMessage()]],
                'completed_at' => now(),
            ]);

            Log::error('Import failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Import single recipe from CSV row
     */
    protected function importRecipeFromCSV(Import $import, array $row): void
    {
        $mapping = $import->mapping ?? $this->getDefaultCSVMapping();

        // Map fields
        $data = [];
        foreach ($mapping as $csvField => $modelField) {
            if (isset($row[$csvField])) {
                $data[$modelField] = $row[$csvField];
            }
        }

        // Handle category
        if (isset($data['category'])) {
            $category = Category::firstOrCreate([
                'tenant_id' => $import->tenant_id,
                'slug' => \Str::slug($data['category']),
                'environment' => 'production',
            ], [
                'name' => $data['category'],
            ]);

            $data['category_id'] = $category->id;
            unset($data['category']);
        }

        // Handle JSON fields
        if (isset($data['ingredients']) && is_string($data['ingredients'])) {
            $data['ingredients'] = json_decode($data['ingredients'], true)
                ?? explode("\n", $data['ingredients']);
        }

        if (isset($data['instructions']) && is_string($data['instructions'])) {
            $data['instructions'] = json_decode($data['instructions'], true)
                ?? explode("\n", $data['instructions']);
        }

        // Generate slug if not provided
        if (! isset($data['slug']) && isset($data['title'])) {
            $data['slug'] = \Str::slug($data['title']);
        }

        // Set defaults
        $data['tenant_id'] = $import->tenant_id;
        $data['environment'] = 'production';
        $data['status'] = $data['status'] ?? 'draft';

        // Check for duplicates
        $updateExisting = $import->options['update_existing'] ?? false;

        if ($updateExisting && isset($data['slug'])) {
            $existing = Recipe::where('tenant_id', $import->tenant_id)
                ->where('slug', $data['slug'])
                ->first();

            if ($existing) {
                $existing->update($data);

                return;
            }
        }

        // Create new recipe
        Recipe::create($data);
    }

    /**
     * Process WordPress WXR import
     */
    public function processWordPress(Import $import): void
    {
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $xml = simplexml_load_file(Storage::path($import->file_path));
            $xml->registerXPathNamespace('wp', 'http://wordpress.org/export/1.2/');
            $xml->registerXPathNamespace('content', 'http://purl.org/rss/1.0/modules/content/');

            $posts = $xml->xpath('//item[wp:post_type="post"]');
            $total = count($posts);

            $import->update(['total_items' => $total]);

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($posts as $index => $post) {
                try {
                    $this->importRecipeFromWordPress($import, $post);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'post' => (string) $post->title,
                        'error' => $e->getMessage(),
                    ];
                    $errorCount++;
                }

                $import->update(['processed_items' => $index + 1]);
            }

            $import->update([
                'status' => $errorCount > 0 ? 'completed_with_errors' : 'completed',
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'errors' => [['error' => $e->getMessage()]],
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Import recipe from WordPress post
     */
    protected function importRecipeFromWordPress(Import $import, SimpleXMLElement $post): void
    {
        $namespaces = $post->getNamespaces(true);
        $wp = $post->children($namespaces['wp']);
        $content = $post->children($namespaces['content']);

        $data = [
            'tenant_id' => $import->tenant_id,
            'environment' => 'production',
            'title' => (string) $post->title,
            'slug' => (string) $wp->post_name,
            'description' => strip_tags((string) $content->encoded),
            'status' => (string) $wp->post_status === 'publish' ? 'published' : 'draft',
            'ingredients' => ['Imported from WordPress'], // Placeholder
            'instructions' => ['See description'], // Placeholder
            'prep_time' => 30,
            'cook_time' => 30,
            'servings' => 4,
        ];

        Recipe::create($data);
    }

    /**
     * Process JSON import
     */
    public function processJSON(Import $import): void
    {
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $json = json_decode(Storage::get($import->file_path), true);

            if (! isset($json['recipes']) && ! is_array($json)) {
                throw new \Exception('Invalid JSON format. Expected {"recipes": [...]}');
            }

            $recipes = $json['recipes'] ?? $json;
            $total = count($recipes);

            $import->update(['total_items' => $total]);

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($recipes as $index => $recipeData) {
                try {
                    $this->importRecipeFromJSON($import, $recipeData);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'recipe' => $recipeData['title'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ];
                    $errorCount++;
                }

                $import->update(['processed_items' => $index + 1]);
            }

            $import->update([
                'status' => $errorCount > 0 ? 'completed_with_errors' : 'completed',
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'errors' => [['error' => $e->getMessage()]],
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Import recipe from JSON data
     */
    protected function importRecipeFromJSON(Import $import, array $data): void
    {
        // Handle category
        if (isset($data['category'])) {
            $categoryName = is_array($data['category']) ? $data['category']['name'] : $data['category'];

            $category = Category::firstOrCreate([
                'tenant_id' => $import->tenant_id,
                'slug' => \Str::slug($categoryName),
                'environment' => 'production',
            ], [
                'name' => $categoryName,
            ]);

            $data['category_id'] = $category->id;
            unset($data['category']);
        }

        // Set required fields
        $data['tenant_id'] = $import->tenant_id;
        $data['environment'] = 'production';

        if (! isset($data['slug']) && isset($data['title'])) {
            $data['slug'] = \Str::slug($data['title']);
        }

        Recipe::create($data);
    }

    /**
     * Get default CSV field mapping
     */
    protected function getDefaultCSVMapping(): array
    {
        return [
            'Title' => 'title',
            'Description' => 'description',
            'Category' => 'category',
            'Prep Time' => 'prep_time',
            'Cook Time' => 'cook_time',
            'Servings' => 'servings',
            'Ingredients' => 'ingredients',
            'Instructions' => 'instructions',
            'Image' => 'image',
            'Nutrition' => 'nutrition',
        ];
    }
}
