<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Filter;

use Daktela\McpServer\Filter\FilterHelper;
use PHPUnit\Framework\TestCase;

final class FilterHelperTest extends TestCase
{
    public function testDropsNullValues(): void
    {
        $result = FilterHelper::fromNullable([
            ['type', 'eq', 'CALL'],
            ['action', 'eq', null],
            ['queue', 'eq', 'support'],
            ['ticket', 'eq', null],
            ['user', 'eq', 'john.doe'],
        ]);

        self::assertSame([
            ['type', 'eq', 'CALL'],
            ['queue', 'eq', 'support'],
            ['user', 'eq', 'john.doe'],
        ], $result);
    }

    public function testKeepsAllWhenNoneNull(): void
    {
        $result = FilterHelper::fromNullable([
            ['type', 'eq', 'EMAIL'],
            ['user', 'eq', 'jane'],
        ]);

        self::assertSame([
            ['type', 'eq', 'EMAIL'],
            ['user', 'eq', 'jane'],
        ], $result);
    }

    public function testReturnsEmptyWhenAllNull(): void
    {
        $result = FilterHelper::fromNullable([
            ['type', 'eq', null],
            ['action', 'eq', null],
        ]);

        self::assertSame([], $result);
    }

    public function testReturnsEmptyForEmptyInput(): void
    {
        $result = FilterHelper::fromNullable([]);

        self::assertSame([], $result);
    }

    public function testPreservesListValues(): void
    {
        $result = FilterHelper::fromNullable([
            ['contact', 'in', ['c1', 'c2', 'c3']],
            ['user', 'eq', null],
        ]);

        self::assertSame([
            ['contact', 'in', ['c1', 'c2', 'c3']],
        ], $result);
    }
}
