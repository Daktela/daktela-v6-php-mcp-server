<?php

declare(strict_types=1);

namespace Daktela\McpServer\Config;

final readonly class DaktelaConfig
{
    public function __construct(
        public string $url,
        public ?string $token = null,
        public ?string $username = null,
        public ?string $password = null,
    ) {}

    public function cacheIdentity(): string
    {
        return $this->url . '|' . ($this->username ?? $this->token ?? '');
    }
}
