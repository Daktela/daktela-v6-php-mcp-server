<?php

declare(strict_types=1);

namespace Daktela\McpServer\Auth;

use Daktela\McpServer\Config\ConfigResolver;
use Daktela\McpServer\Config\DaktelaConfig;

final class AuthResolver implements AuthResolverInterface
{
    public function resolve(?array $headers = null): DaktelaConfig
    {
        if ($headers !== null) {
            // Mode 1: X-Daktela headers
            $config = $this->resolveFromDaktelaHeaders($headers);
            if ($config !== null) {
                return $config;
            }
        }

        // Mode 2: Environment variables
        return (new ConfigResolver())->resolve();
    }

    /**
     * @param array<string, string> $headers
     */
    private function resolveFromDaktelaHeaders(array $headers): ?DaktelaConfig
    {
        $url = $headers['x-daktela-url'] ?? '';
        if ($url === '') {
            return null;
        }

        $url = rtrim($url, '/');

        $username = $headers['x-daktela-username'] ?? '';
        $password = $headers['x-daktela-password'] ?? '';
        $token = $headers['x-daktela-access-token'] ?? '';

        if ($username !== '' && $password !== '') {
            return new DaktelaConfig(url: $url, username: $username, password: $password);
        }

        if ($token !== '') {
            return new DaktelaConfig(url: $url, token: $token);
        }

        return null;
    }
}
