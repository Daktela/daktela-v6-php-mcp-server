<?php

declare(strict_types=1);

namespace Daktela\McpServer\Http;

use Daktela\McpServer\Auth\AuthResolver;
use Daktela\McpServer\Cache\ReferenceDataCache;
use Daktela\McpServer\Client\DaktelaClient;
use Daktela\McpServer\DaktelaMcpServer;
use Daktela\McpServer\Session\ResilientSessionStore;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Session\Psr16SessionStore;
use Mcp\Server\Session\SessionStoreInterface;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class HttpRequestHandler
{
    private readonly Psr17Factory $psr17Factory;
    private readonly AuthResolver $authResolver;
    private readonly string $corsOrigin;

    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->psr17Factory = new Psr17Factory();
        $this->authResolver = new AuthResolver();
        $this->corsOrigin = ($_ENV['CORS_ORIGIN'] ?? '') !== '' ? $_ENV['CORS_ORIGIN'] : '*';
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Handle CORS preflight for any path
        if ($method === 'OPTIONS') {
            return $this->handleCorsPreflightResponse();
        }

        $response = match (true) {
            $path === '/health' && $method === 'GET'
                => $this->handleHealth(),

            $path === '/mcp' && \in_array($method, ['GET', 'POST', 'DELETE'], true)
                => $this->handleMcp($request),

            default => $this->createResponse(404, ['error' => 'Not found']),
        };

        return $this->withCorsHeaders($response);
    }

    private function handleHealth(): ResponseInterface
    {
        try {
            $config = $this->authResolver->resolve();
        } catch (\Throwable) {
            return $this->createResponse(200, [
                'status' => 'ok',
                'authenticated' => false,
                'message' => 'Server running, but no credentials configured for health check.',
            ]);
        }

        try {
            $client = new DaktelaClient($config, new ReferenceDataCache(), $this->logger);
            $client->login();
            $whoami = $client->get('whoami', '');

            return $this->createResponse(200, [
                'status' => 'ok',
                'authenticated' => true,
                'instance' => $config->url,
                'user' => $whoami['title'] ?? $whoami['name'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            return $this->createResponse(503, [
                'status' => 'error',
                'authenticated' => false,
                'message' => 'Cannot connect to Daktela instance: ' . $e->getMessage(),
            ]);
        }
    }

    private function handleMcp(ServerRequestInterface $request): ResponseInterface
    {
        // Resolve Daktela credentials from environment variables
        try {
            $config = $this->authResolver->resolve();
        } catch (\Throwable $e) {
            $this->logger->error('Auth resolution failed', ['error' => $e->getMessage()]);
            return $this->createResponse(401, [
                'error' => 'unauthorized',
                'error_description' => 'Could not resolve Daktela credentials.',
            ]);
        }

        try {
            // Create a per-request DaktelaClient with cache
            $client = new DaktelaClient($config, new ReferenceDataCache(), $this->logger);
            $client->login();

            // Build MCP server with persistent sessions for cross-request state
            $server = DaktelaMcpServer::buildServer($client, $this->logger, $this->createSessionStore());
            $transport = new StreamableHttpTransport(
                $request,
                $this->psr17Factory,
                $this->psr17Factory,
                logger: $this->logger,
            );

            /** @var ResponseInterface */
            return $server->run($transport);
        } catch (\Throwable $e) {
            $this->logger->error('MCP request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->createResponse(500, [
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32603,
                    'message' => 'Internal error: ' . $e->getMessage(),
                ],
                'id' => null,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createResponse(int $status, array $data): ResponseInterface
    {
        $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return $this->psr17Factory->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->psr17Factory->createStream($body));
    }

    private function handleCorsPreflightResponse(): ResponseInterface
    {
        return $this->withCorsHeaders(
            $this->psr17Factory->createResponse(204),
        );
    }

    private function createSessionStore(): SessionStoreInterface
    {
        $url = ($_ENV['SESSION_STORE_URL'] ?? '') !== '' ? $_ENV['SESSION_STORE_URL'] : null;

        if ($url !== null) {
            $cache = new Psr16Cache(new RedisAdapter(RedisAdapter::createConnection($url)));
            $inner = new Psr16SessionStore($cache, 'mcp-', 3600);
        } else {
            $sessionDir = ($_ENV['SESSION_DIR'] ?? '') !== '' ? $_ENV['SESSION_DIR'] : '/app/var/sessions';
            $inner = new FileSessionStore($sessionDir);
        }

        return new ResilientSessionStore($inner, $this->logger);
    }

    private function withCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->corsOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Mcp-Session-Id')
            ->withHeader('Access-Control-Max-Age', '86400');
    }
}
