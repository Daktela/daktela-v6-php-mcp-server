<?php

declare(strict_types=1);

namespace Daktela\McpServer\Resolver;

use Daktela\McpServer\Client\DaktelaClientInterface;

final class FolderResolver
{
    /**
     * Resolve a folder name or title to the internal folder name (ID).
     *
     * Tries an exact get by name first. If not found, searches by title
     * with a fuzzy (like) match and returns the first result.
     *
     * @return string|null The folder name (internal ID), or null if not found.
     */
    public static function resolve(DaktelaClientInterface $client, string $folderInput): ?string
    {
        // Try exact lookup by name
        $record = $client->get('articlesFolders', $folderInput);
        if ($record !== null) {
            return $record['name'] ?? $folderInput;
        }

        // Search by title with fuzzy match
        $result = $client->list(
            'articlesFolders',
            fieldFilters: [['title', 'like', $folderInput]],
            take: 1,
        );

        if ($result['data'] !== []) {
            return $result['data'][0]['name'] ?? null;
        }

        return null;
    }
}
