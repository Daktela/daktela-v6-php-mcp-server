<?php

declare(strict_types=1);

namespace Daktela\McpServer\Auth;

use Daktela\McpServer\Config\DaktelaConfig;

interface AuthResolverInterface
{
    /**
     * Resolve Daktela configuration from request headers or environment.
     *
     * @param array<string, string>|null $headers HTTP request headers (lowercase keys).
     */
    public function resolve(?array $headers = null): DaktelaConfig;
}
