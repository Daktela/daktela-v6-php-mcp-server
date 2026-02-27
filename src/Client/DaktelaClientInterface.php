<?php

declare(strict_types=1);

namespace Daktela\McpServer\Client;

interface DaktelaClientInterface
{
    /**
     * Fetch a paginated list from a Daktela endpoint.
     *
     * @param list<array{string, string, string|list<string>}>|null $fieldFilters
     * @param list<string>|null $fields
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function list(
        string $endpoint,
        ?array $fieldFilters = null,
        int $skip = 0,
        int $take = 100,
        ?string $sort = null,
        string $sortDir = 'desc',
        ?array $fields = null,
        ?string $search = null,
    ): array;

    /**
     * Fetch a single record by name/ID.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $endpoint, string $name): ?array;

    public function getBaseUrl(): string;

    public function getCacheIdentity(): string;
}
