<?php

declare(strict_types=1);

namespace Daktela\McpServer\Client;

use Daktela\DaktelaV6\Client as OfficialClient;
use Daktela\DaktelaV6\Exception\RequestException;
use Daktela\DaktelaV6\Http\RateLimitConfig;
use Daktela\DaktelaV6\Http\RetryConfig;
use Daktela\DaktelaV6\RequestFactory;
use Daktela\McpServer\Cache\ReferenceDataCache;
use Daktela\McpServer\Config\DaktelaConfig;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class DaktelaClient implements DaktelaClientInterface
{
    private ?string $token;
    private ?string $refreshToken = null;
    private float $tokenExpiresAt = 0;
    private ?OfficialClient $officialClient = null;
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly DaktelaConfig $config,
        private readonly ?ReferenceDataCache $cache = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->token = $config->token;
        $this->logger = $logger ?? new NullLogger();
    }

    public function login(): void
    {
        if ($this->config->username !== null && $this->config->password !== null && $this->token === null) {
            $this->doLogin();
        }

        $this->initOfficialClient();
    }

    public function getBaseUrl(): string
    {
        return $this->config->url;
    }

    public function getCacheIdentity(): string
    {
        return $this->config->cacheIdentity();
    }

    public function list(
        string $endpoint,
        ?array $fieldFilters = null,
        int $skip = 0,
        int $take = 100,
        ?string $sort = null,
        string $sortDir = 'desc',
        ?array $fields = null,
        ?string $search = null,
    ): array {
        $this->ensureToken();

        // Check cache for unfiltered reference data queries
        $cacheable = $fieldFilters === null && $search === null && $fields === null && $this->cache !== null;
        if ($cacheable) {
            $cached = $this->cache->get($this->config->cacheIdentity(), $endpoint, $skip, $take, $sort, $sortDir);
            if ($cached !== null) {
                return $cached;
            }
        }

        $request = RequestFactory::buildReadRequest($endpoint);
        $request->setSkip($skip)->setTake($take);

        if ($fieldFilters !== null) {
            foreach ($fieldFilters as [$field, $operator, $value]) {
                if ($operator === 'like' && \is_string($value) && !str_contains($value, '%')) {
                    $value = "%{$value}%";
                }
                $request->addFilter($field, $operator, $value);
            }
        }

        if ($sort !== null) {
            $request->addSort($sort, $sortDir);
        }

        if ($fields !== null && $fields !== []) {
            $request->setFields($fields);
        }

        if ($search !== null && $search !== '') {
            $request->addAdditionalQueryParameter('q', $search);
        }

        try {
            $response = $this->getClient()->execute($request);
        } catch (RequestException $e) {
            throw new DaktelaApiException(
                $endpoint,
                $e->getCode() !== 0 ? $e->getCode() : null,
                $e->getMessage(),
            );
        }

        $rawData = $response->getData();
        $records = $this->toArrayList($rawData);
        $total = $response->getTotal();

        $result = ['data' => $records, 'total' => $total];

        if ($cacheable) {
            $this->cache->put($this->config->cacheIdentity(), $endpoint, $skip, $take, $sort, $sortDir, $result);
        }

        return $result;
    }

    public function get(string $endpoint, string $name): ?array
    {
        $this->ensureToken();

        $request = RequestFactory::buildReadSingleRequest($endpoint, $name);

        try {
            $response = $this->getClient()->execute($request);
        } catch (RequestException $e) {
            // 404 means "not found" — return null as expected
            if ($e->getCode() === 404) {
                return null;
            }

            throw new DaktelaApiException(
                $endpoint,
                $e->getCode() !== 0 ? $e->getCode() : null,
                $e->getMessage(),
            );
        }

        if (!$response->isSuccess() || $response->isEmpty()) {
            return null;
        }

        return $this->toArray($response->getData());
    }

    private function getClient(): OfficialClient
    {
        if ($this->officialClient === null) {
            $this->initOfficialClient();
        }

        \assert($this->officialClient !== null);

        return $this->officialClient;
    }

    private function initOfficialClient(): void
    {
        $this->officialClient = new OfficialClient($this->config->url, $this->token ?? '');

        $communicator = $this->officialClient->getApiCommunicator();
        if ($communicator !== null) {
            $communicator->setRequestTimeout(30.0);
            $communicator->setRetryConfig(new RetryConfig(maxRetries: 3));
            $communicator->setRateLimitConfig(new RateLimitConfig(autoRetry: true));
        }
    }

    private function doLogin(): void
    {
        $guzzle = new GuzzleClient(['timeout' => 30, 'http_errors' => false]);
        $response = $guzzle->post("{$this->config->url}/api/v6/login.json", [
            'json' => ['username' => $this->config->username, 'password' => $this->config->password],
        ]);

        $body = (string) $response->getBody()->getContents();
        /** @var array{result?: array{accessToken?: string, refreshToken?: string}} $decoded */
        $decoded = json_decode($body, true);
        $result = $decoded['result'] ?? [];
        $this->token = $result['accessToken'] ?? null;
        $this->refreshToken = $result['refreshToken'] ?? null;
        $this->tokenExpiresAt = microtime(true) + 3600 - 60;

        if ($this->token !== null) {
            $this->logger->info('Login successful', ['url' => $this->config->url, 'user' => $this->config->username]);
        } else {
            $this->logger->warning('Login failed — no access token received', ['url' => $this->config->url, 'user' => $this->config->username]);
        }
    }

    private function doRefresh(): void
    {
        $guzzle = new GuzzleClient(['timeout' => 30, 'http_errors' => false]);
        $response = $guzzle->put("{$this->config->url}/api/v6/login.json", [
            'json' => ['refreshToken' => $this->refreshToken],
        ]);

        if ($response->getStatusCode() !== 200) {
            $this->logger->info('Token refresh failed, falling back to re-login', ['url' => $this->config->url]);
            $this->doLogin();

            return;
        }

        $body = (string) $response->getBody()->getContents();
        /** @var array{result?: array{accessToken?: string, refreshToken?: string}} $decoded */
        $decoded = json_decode($body, true);
        $result = $decoded['result'] ?? [];
        $this->token = $result['accessToken'] ?? $this->token;
        $this->refreshToken = $result['refreshToken'] ?? $this->refreshToken;
        $this->tokenExpiresAt = microtime(true) + 3600 - 60;

        $this->logger->info('Token refreshed successfully', ['url' => $this->config->url]);
    }

    private function ensureToken(): void
    {
        if ($this->config->username !== null && $this->tokenExpiresAt > 0 && microtime(true) >= $this->tokenExpiresAt) {
            $this->logger->info('Token expired, refreshing', ['url' => $this->config->url]);
            if ($this->refreshToken !== null) {
                $this->doRefresh();
            } else {
                $this->doLogin();
            }
            $this->initOfficialClient();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function toArrayList(mixed $data): array
    {
        if ($data === null) {
            return [];
        }

        $data = self::toNative($data);

        if (!\is_array($data)) {
            return [];
        }

        if (!array_is_list($data)) {
            $data = array_values($data);
        }

        return array_map(fn($item) => \is_array($item) ? $item : [], $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(mixed $data): array
    {
        $result = self::toNative($data);

        return \is_array($result) ? $result : [];
    }

    /**
     * Recursively convert stdClass objects to associative arrays without
     * the json_encode/json_decode roundtrip that doubles memory usage.
     */
    private static function toNative(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $result = [];
            foreach ((array) $value as $key => $val) {
                $result[$key] = self::toNative($val);
            }

            return $result;
        }

        if (\is_array($value)) {
            foreach ($value as $key => $val) {
                if (\is_object($val) || \is_array($val)) {
                    $value[$key] = self::toNative($val);
                }
            }

            return $value;
        }

        return $value;
    }
}
