<?php

namespace App\Services\Search;

use MeiliSearch\Client;

class MeilisearchProvider implements SearchProviderInterface
{
    protected Client $client;
    protected string $indexName;

    public function __construct(string $tenantId)
    {
        $this->client = new Client(
            config('services.meilisearch.host'),
            config('services.meilisearch.key')
        );

        $this->indexName = "recipes_tenant_{$tenantId}";
    }

    public function search(string $query, array $filters = [], int $limit = 20): array
    {
        $index = $this->client->index($this->indexName);

        $searchParams = [
            'limit' => $limit,
            'attributesToHighlight' => ['title', 'description'],
        ];

        // Build filter string
        if (!empty($filters)) {
            $filterParts = [];

            if (isset($filters['category'])) {
                $filterParts[] = "category = '{$filters['category']}'";
            }

            if (isset($filters['max_prep_time'])) {
                $filterParts[] = "prep_time <= {$filters['max_prep_time']}";
            }

            if (isset($filters['max_cook_time'])) {
                $filterParts[] = "cook_time <= {$filters['max_cook_time']}";
            }

            if (isset($filters['max_total_time'])) {
                $maxTime = $filters['max_total_time'];
                $filterParts[] = "(prep_time + cook_time) <= {$maxTime}";
            }

            if (!empty($filterParts)) {
                $searchParams['filter'] = implode('AND', $filterParts);
            }
        }

        $results = $index->search($query, $searchParams);

        return [
            'hits' => $results->getHits(),
            'total' => $results->getEstimatedTotalHits(),
            'processing_time_ms' => $results->getProcessingTimeMs(),
        ];
    }

    public function addDocuments(array $documents): void
    {
        $index = $this->client->index($this->indexName);
        $index->addDocuments($documents);
    }

    public function updateDocuments(array $documents): void
    {
        $index = $this->client->index($this->indexName);
        $index->updateDocuments($documents);
    }

    public function deleteDocuments(array $documentIds): void
    {
        $index = $this->client->index($this->indexName);
        $index->deleteDocuments($documentIds);
    }

    public function clearIndex(): void
    {
        $index = $this->client->index($this->indexName);
        $index->deleteAllDocuments();
    }

    public function autocomplete(string $query, int $limit = 5): array
    {
        $index = $this->client->index($this->indexName);

        $results = $index->search($query, [
            'limit' => $limit,
            'attributesToRetrieve' => ['id', 'title', 'slug'],
        ]);

        return array_map(function ($hit) {
            return [
                'id' => $hit['id'],
                'title' => $hit['title'],
                'slug' => $hit['slug'],
            ];
        }, $results->getHits());
    }

    public function getStats(): array
    {
        try {
            $index = $this->client->index($this->indexName);
            $stats = $index->stats();

            return [
                'documents_count' => $stats['numberOfDocuments'] ?? 0,
                'is_indexing' => $stats['isIndexing'] ?? false,
                'field_distribution' => $stats['fieldDistribution'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'documents_count' => 0,
                'is_indexing' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Configure searchable attributes
     */
    public function configureIndex(): void
    {
        $index = $this->client->index($this->indexName);

        // Searchable attributes (order matters for relevance)
        $index->updateSearchableAttributes([
            'title',
            'description',
            'ingredients',
            'category',
            'instructions',
        ]);

        // Filterable attributes
        $index->updateFilterableAttributes([
            'category',
            'prep_time',
            'cook_time',
            'servings',
            'status',
        ]);

        // Sortable attributes
        $index->updateSortableAttributes([
            'created_at',
            'prep_time',
            'cook_time',
        ]);

        // Display attributes
        $index->updateDisplayedAttributes([
            'id',
            'title',
            'slug',
            'description',
            'category',
            'prep_time',
            'cook_time',
            'servings',
            'image',
            'created_at',
        ]);
    }
}
