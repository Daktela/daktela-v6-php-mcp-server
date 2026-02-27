<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests;

use Daktela\McpServer\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testGetReturnsString(): void
    {
        $version = Version::get();
        self::assertIsString($version);
        self::assertNotEmpty($version);
    }

    public function testGetReturnsSameValueOnMultipleCalls(): void
    {
        $v1 = Version::get();
        $v2 = Version::get();
        self::assertSame($v1, $v2);
    }
}
