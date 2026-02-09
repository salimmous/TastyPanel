<?php

namespace App\Services\Search;

interface SearchProviderInterface
{
    /**
     * Search for documents
     */
    public function search(string $query, array $filters = [], int $limit = 20): array;

    /**
     * Add documents to index
     */
    public function addDocuments(array $documents): void;

    /**
     * Update documents in index
     */
    public function updateDocuments(array $documents): void;

    /**
     * Delete documents from index
     */
    public function deleteDocuments(array $documentIds): void;

    /**
     * Clear all documents from index
     */
    public function clearIndex(): void;

    /**
     * Autocomplete suggestions
     */
    public function autocomplete(string $query, int $limit = 5): array;

    /**
     * Get index statistics
     */
    public function getStats(): array;
}
