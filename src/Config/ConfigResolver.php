<?php

declare(strict_types=1);

namespace Daktela\McpServer\Config;

final class ConfigResolver
{
    public function resolve(): DaktelaConfig
    {
        $url = getenv('DAKTELA_URL') ?: '';
        if ($url === '') {
            throw new \RuntimeException('DAKTELA_URL environment variable is required');
        }

        $url = rtrim($url, '/');
        $username = getenv('DAKTELA_USERNAME') ?: '';
        $password = getenv('DAKTELA_PASSWORD') ?: '';
        $token = getenv('DAKTELA_ACCESS_TOKEN') ?: '';

        if ($username !== '' && $password !== '') {
            return new DaktelaConfig(url: $url, username: $username, password: $password);
        }

        if ($token !== '') {
            return new DaktelaConfig(url: $url, token: $token);
        }

        throw new \RuntimeException(
            'Either DAKTELA_USERNAME + DAKTELA_PASSWORD or DAKTELA_ACCESS_TOKEN '
            . 'environment variables are required'
        );
    }
}
