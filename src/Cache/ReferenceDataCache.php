<?php

declare(strict_types=1);

namespace Daktela\McpServer\Cache;

final class ReferenceDataCache
{
    private const CACHEABLE_ENDPOINTS = [
        'users',
        'queues',
        'ticketsCategories',
        'groups',
        'pauses',
        'statuses',
        'templates',
        'campaignsTypes',
    ];

    private const DEFAULT_TTL = 3600; // 60 minutes

    /** @var array<string, array{float, array{data: list<array<string, mixed>>, total: int}}> */
    private array $store = [];

    /**
     * @return array{data: list<array<string, mixed>>, total: int}|null
     */
    public function get(
        string $identity,
        string $endpoint,
        int $skip,
        int $take,
        ?string $sort,
        string $sortDir,
    ): ?array {
        if (!$this->isEnabled() || !\in_array($endpoint, self::CACHEABLE_ENDPOINTS, true)) {
            return null;
        }

        $key = $this->buildKey($identity, $endpoint, $skip, $take, $sort, $sortDir);
        $entry = $this->store[$key] ?? null;

        if ($entry === null) {
            return null;
        }

        [$expiresAt, $data] = $entry;
        if (microtime(true) > $expiresAt) {
            unset($this->store[$key]);

            return null;
        }

        return $data;
    }

    /**
     * @param array{data: list<array<string, mixed>>, total: int} $data
     */
    public function put(
        string $identity,
        string $endpoint,
        int $skip,
        int $take,
        ?string $sort,
        string $sortDir,
        array $data,
    ): void {
        if (!$this->isEnabled() || !\in_array($endpoint, self::CACHEABLE_ENDPOINTS, true)) {
            return;
        }

        // Prune expired entries to prevent unbounded growth
        $now = microtime(true);
        foreach ($this->store as $k => [$expiresAt]) {
            if ($now > $expiresAt) {
                unset($this->store[$k]);
            }
        }

        $key = $this->buildKey($identity, $endpoint, $skip, $take, $sort, $sortDir);
        /** @var array{data: list<array<string, mixed>>, total: int} $data */
        $this->store[$key] = [$now + $this->getTtl(), $data];
    }

    public function clear(): void
    {
        $this->store = [];
    }

    private function isEnabled(): bool
    {
        $val = getenv('CACHE_ENABLED') ?: 'true';

        return !\in_array(strtolower($val), ['false', '0', 'no'], true);
    }

    private function getTtl(): float
    {
        $val = getenv('CACHE_TTL_SECONDS');

        return $val !== false ? (float) $val : (float) self::DEFAULT_TTL;
    }

    private function buildKey(
        string $identity,
        string $endpoint,
        int $skip,
        int $take,
        ?string $sort,
        string $sortDir,
    ): string {
        return implode('|', [$identity, $endpoint, $skip, $take, $sort ?? '', $sortDir]);
    }
}
