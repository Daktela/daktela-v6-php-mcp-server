<?php

declare(strict_types=1);

namespace Daktela\McpServer\Session;

use Mcp\Server\Session\SessionStoreInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;

/**
 * Decorator that prevents session-loss errors (e.g. after container restart)
 * by returning an empty session instead of rejecting the request.
 */
final class ResilientSessionStore implements SessionStoreInterface
{
    public function __construct(
        private readonly SessionStoreInterface $inner,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function exists(Uuid $id): bool
    {
        if (!$this->inner->exists($id)) {
            $this->logger->warning('Session not found, allowing recovery', ['session' => (string) $id]);
        }

        return true;
    }

    public function read(Uuid $id): string
    {
        $data = $this->inner->read($id);

        if ($data === false) {
            $this->logger->warning('Session data missing, returning empty state', ['session' => (string) $id]);

            return '{}';
        }

        return $data;
    }

    public function write(Uuid $id, string $data): bool
    {
        return $this->inner->write($id, $data);
    }

    public function destroy(Uuid $id): bool
    {
        return $this->inner->destroy($id);
    }

    public function gc(): array
    {
        return $this->inner->gc();
    }
}
