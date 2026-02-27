<?php

declare(strict_types=1);

namespace Daktela\McpServer\Client;

final class DaktelaApiException extends \RuntimeException
{
    public function __construct(
        public readonly string $endpoint,
        public readonly ?int $httpStatus,
        string $message,
    ) {
        parent::__construct($message);
    }
}
