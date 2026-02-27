<?php

declare(strict_types=1);

namespace Daktela\McpServer\Resolver;

use Daktela\McpServer\Client\DaktelaClientInterface;

final class TagResolver
{
    /**
     * Resolve a tag name or title to the internal tag name (ID).
     *
     * Tries an exact get by name first. If not found, searches by title
     * with a fuzzy (like) match and returns the first result.
     *
     * @return string|null The tag name (internal ID), or null if not found.
     */
    public static function resolve(DaktelaClientInterface $client, string $tagInput): ?string
    {
        // Try exact lookup by name
        $record = $client->get('articlesTags', $tagInput);
        if ($record !== null) {
            return $record['name'] ?? $tagInput;
        }

        // Search by title with fuzzy match
        $result = $client->list(
            'articlesTags',
            fieldFilters: [['title', 'like', $tagInput]],
            take: 1,
        );

        if ($result['data'] !== []) {
            return $result['data'][0]['name'] ?? null;
        }

        return null;
    }
}
