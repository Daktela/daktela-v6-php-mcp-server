<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Config;

use Daktela\McpServer\Config\DaktelaConfig;
use PHPUnit\Framework\TestCase;

final class DaktelaConfigTest extends TestCase
{
    public function testCacheIdentityWithToken(): void
    {
        $config = new DaktelaConfig(url: 'https://example.com', token: 'abc123');
        self::assertSame('https://example.com|abc123', $config->cacheIdentity());
    }

    public function testCacheIdentityWithUsername(): void
    {
        $config = new DaktelaConfig(url: 'https://example.com', username: 'john', password: 'secret');
        self::assertSame('https://example.com|john', $config->cacheIdentity());
    }

    public function testReadonlyProperties(): void
    {
        $config = new DaktelaConfig(
            url: 'https://test.daktela.com',
            token: 'token123',
        );
        self::assertSame('https://test.daktela.com', $config->url);
        self::assertSame('token123', $config->token);
        self::assertNull($config->username);
        self::assertNull($config->password);
    }
}
