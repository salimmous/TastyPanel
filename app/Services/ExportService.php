<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Export;
use App\Models\Recipe;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use League\Csv\Writer;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportService
{
    /**
     * Create a new export record
     */
    public function create(Tenant $tenant, string $format, string $type = 'recipe', array $filters = [], array $options = []): Export
    {
        return Export::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'format' => $format,
            'type' => $type,
            'status' => 'pending',
            'filters' => $filters,
            'options' => $options,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Process CSV export
     */
    public function generateCSV(Export $export): void
    {
        $export->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $items = $this->getItems($export);
            $export->update(['total_items' => $items->count()]);

            $filename = "export_{$export->type}_{$export->id}.csv";
            $path = "exports/{$export->tenant_id}/{$filename}";

            Storage::put($path, '');
            $fullPath = Storage::path($path);

            $csv = Writer::createFromPath($fullPath, 'w+');
            $csv->setDelimiter(',');

            // Write headers
            $headers = $this->getCSVHeaders($export->type);
            $csv->insertOne($headers);

            // Write data
            foreach ($items as $index => $item) {
                $row = $this->formatItemForCSV($item, $export->type);
                $csv->insertOne($row);

                $export->update(['processed_items' => $index + 1]);
            }

            $export->update([
                'status' => 'completed',
                'file_path' => $path,
                'filename' => $filename,
                'file_size' => Storage::size($path),
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Export failed', [
                'export_id' => $export->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate JSON export
     */
    public function generateJSON(Export $export): void
    {
        $export->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $items = $this->getItems($export);
            $export->update(['total_items' => $items->count()]);

            $data = [
                'export_date' => now()->toIso8601String(),
                'tenant' => $export->tenant->name,
                'type' => $export->type,
                'total' => $items->count(),
                'items' => [],
            ];

            foreach ($items as $index => $item) {
                $data['items'][] = $this->formatItemForJSON($item, $export->type);
                $export->update(['processed_items' => $index + 1]);
            }

            $filename = "export_{$export->type}_{$export->id}.json";
            $path = "exports/{$export->tenant_id}/{$filename}";

            Storage::put($path, json_encode($data, JSON_PRETTY_PRINT));

            $export->update([
                'status' => 'completed',
                'file_path' => $path,
                'filename' => $filename,
                'file_size' => Storage::size($path),
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Generate WordPress WXR export
     */
    public function generateWordPress(Export $export): void
    {
        $export->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $items = $this->getItems($export);
            $export->update(['total_items' => $items->count()]);

            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
            $xml->addAttribute('xmlns:xmlns:wp', 'http://wordpress.org/export/1.2/');
            $xml->addAttribute('xmlns:xmlns:content', 'http://purl.org/rss/1.0/modules/content/');

            $channel = $xml->addChild('channel');
            $channel->addChild('title', $export->tenant->name);
            $channel->addChild('link', "https://{$export->tenant->primary_domain}");

            foreach ($items as $index => $recipe) {
                $item = $channel->addChild('item');
                $item->addChild('title', htmlspecialchars($recipe->title));
                $item->addChild('link', "https://{$export->tenant->primary_domain}/recipes/{$recipe->slug}");
                $item->addChild('description', htmlspecialchars($recipe->description));

                $content = $item->addChild('content:encoded', htmlspecialchars($recipe->description));
                $item->addChild('wp:post_type', 'post');
                $item->addChild('wp:post_name', $recipe->slug);
                $item->addChild('wp:status', $recipe->status === 'published' ? 'publish' : 'draft');

                $export->update(['processed_items' => $index + 1]);
            }

            $filename = "export_wordpress_{$export->id}.xml";
            $path = "exports/{$export->tenant_id}/{$filename}";

            Storage::put($path, $xml->asXML());

            $export->update([
                'status' => 'completed',
                'file_path' => $path,
                'filename' => $filename,
                'file_size' => Storage::size($path),
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Get items to export based on filters
     */
    protected function getItems(Export $export)
    {
        $query = match ($export->type) {
            'recipe' => Recipe::query(),
            'article' => Article::query(),
            'category' => Category::query(),
            default => Recipe::query(),
        };

        $query->where('tenant_id', $export->tenant_id);

        // Apply filters
        if (isset($export->filters['category_id'])) {
            $query->where('category_id', $export->filters['category_id']);
        }

        if (isset($export->filters['status'])) {
            $query->where('status', $export->filters['status']);
        }

        if (isset($export->filters['date_from'])) {
            $query->where('created_at', '>=', $export->filters['date_from']);
        }

        if (isset($export->filters['date_to'])) {
            $query->where('created_at', '<=', $export->filters['date_to']);
        }

        // Load relationships
        if ($export->type === 'recipe') {
            $query->with('category');
        }

        return $query->get();
    }

    /**
     * Get CSV headers based on type
     */
    protected function getCSVHeaders(string $type): array
    {
        return match ($type) {
            'recipe' => [
                'ID',
                'Title',
                'Slug',
                'Description',
                'Category',
                'Prep Time',
                'Cook Time',
                'Servings',
                'Ingredients',
                'Instructions',
                'Nutrition',
                'Image',
                'Status',
                'Created At'
            ],
            'article' => [
                'ID',
                'Title',
                'Slug',
                'Content',
                'Category',
                'Image',
                'Status',
                'Published At',
                'Created At'
            ],
            'category' => [
                'ID',
                'Name',
                'Slug',
                'Description',
                'Image',
                'Created At'
            ],
            default => ['ID', 'Data'],
        };
    }

    /**
     * Format item for CSV export
     */
    protected function formatItemForCSV($item, string $type): array
    {
        if ($type === 'recipe') {
            return [
                $item->id,
                $item->title,
                $item->slug,
                $item->description,
                $item->category?->name ?? '',
                $item->prep_time,
                $item->cook_time,
                $item->servings,
                json_encode($item->ingredients),
                json_encode($item->instructions),
                json_encode($item->nutrition),
                $item->image,
                $item->status,
                $item->created_at->toDateTimeString(),
            ];
        }

        return [(string) $item->id, json_encode($item->toArray())];
    }

    /**
     * Format item for JSON export
     */
    protected function formatItemForJSON($item, string $type): array
    {
        if ($type === 'recipe') {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'description' => $item->description,
                'category' => $item->category?->name,
                'prep_time' => $item->prep_time,
                'cook_time' => $item->cook_time,
                'servings' => $item->servings,
                'ingredients' => $item->ingredients,
                'instructions' => $item->instructions,
                'nutrition' => $item->nutrition,
                'image' => $item->image,
                'status' => $item->status,
                'created_at' => $item->created_at->toIso8601String(),
            ];
        }

        return $item->toArray();
    }

    /**
     * Record download
     */
    public function recordDownload(Export $export): void
    {
        $export->increment('download_count');
        $export->update(['last_downloaded_at' => now()]);
    }

    /**
     * Clean up expired exports
     */
    public function cleanupExpired(): int
    {
        $exports = Export::where('expires_at', '<', now())
            ->where('status', 'completed')
            ->get();

        $count = 0;
        foreach ($exports as $export) {
            if ($export->file_path) {
                Storage::delete($export->file_path);
            }
            $export->delete();
            $count++;
        }

        return $count;
    }
}
