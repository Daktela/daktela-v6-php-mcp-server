<?php

declare(strict_types=1);

namespace Daktela\McpServer\Resolver;

use Daktela\McpServer\Client\DaktelaClientInterface;

final class UserResolver
{
    /**
     * Resolve a user display name or login name to [loginName, displayName].
     *
     * Searches by display name (title) first. If there's an exact match, uses it.
     * Otherwise falls back to the first partial match. If nothing matches by title,
     * tries matching by login name. Returns the input as-is if nothing matches.
     *
     * @return array{string, string|null}
     */
    public static function resolve(DaktelaClientInterface $client, string $userInput): array
    {
        // Search by display name
        $result = $client->list(
            'users',
            fieldFilters: [['title', 'like', $userInput]],
            take: 10,
            fields: ['name', 'title'],
        );

        if ($result['data'] !== []) {
            foreach ($result['data'] as $u) {
                if (mb_strtolower(trim($u['title'] ?? '')) === mb_strtolower(trim($userInput))) {
                    return [$u['name'], $u['title'] ?? null];
                }
            }

            return [$result['data'][0]['name'], $result['data'][0]['title'] ?? null];
        }

        // Search by login name
        $result = $client->list(
            'users',
            fieldFilters: [['name', 'like', $userInput]],
            take: 10,
            fields: ['name', 'title'],
        );

        if ($result['data'] !== []) {
            foreach ($result['data'] as $u) {
                if (mb_strtolower($u['name']) === mb_strtolower(trim($userInput))) {
                    return [$u['name'], $u['title'] ?? null];
                }
            }

            return [$result['data'][0]['name'], $result['data'][0]['title'] ?? null];
        }

        return [$userInput, null];
    }
}
