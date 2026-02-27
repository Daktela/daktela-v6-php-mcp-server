<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Cache;

use Daktela\McpServer\Cache\ReferenceDataCache;
use PHPUnit\Framework\TestCase;

final class ReferenceDataCacheTest extends TestCase
{
    private ReferenceDataCache $cache;

    protected function setUp(): void
    {
        $this->cache = new ReferenceDataCache();
        $this->cache->clear();
    }

    public function testGetMissReturnsNull(): void
    {
        self::assertNull($this->cache->get('id', 'users', 0, 50, null, 'desc'));
    }

    public function testPutAndGet(): void
    {
        $data = ['data' => [['name' => 'john']], 'total' => 1];
        $this->cache->put('id', 'users', 0, 50, null, 'desc', $data);

        $result = $this->cache->get('id', 'users', 0, 50, null, 'desc');
        self::assertSame($data, $result);
    }

    public function testNonCacheableEndpointIgnored(): void
    {
        $data = ['data' => [], 'total' => 0];
        $this->cache->put('id', 'tickets', 0, 50, null, 'desc', $data);

        self::assertNull($this->cache->get('id', 'tickets', 0, 50, null, 'desc'));
    }

    public function testClearRemovesAll(): void
    {
        $data = ['data' => [], 'total' => 0];
        $this->cache->put('id', 'users', 0, 50, null, 'desc', $data);
        $this->cache->clear();

        self::assertNull($this->cache->get('id', 'users', 0, 50, null, 'desc'));
    }

    public function testDifferentIdentitiesAreSeparate(): void
    {
        $data1 = ['data' => [['name' => 'a']], 'total' => 1];
        $data2 = ['data' => [['name' => 'b']], 'total' => 1];

        $this->cache->put('tenant1', 'users', 0, 50, null, 'desc', $data1);
        $this->cache->put('tenant2', 'users', 0, 50, null, 'desc', $data2);

        self::assertSame($data1, $this->cache->get('tenant1', 'users', 0, 50, null, 'desc'));
        self::assertSame($data2, $this->cache->get('tenant2', 'users', 0, 50, null, 'desc'));
    }
}
