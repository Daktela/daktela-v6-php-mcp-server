<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Filter;

use Daktela\McpServer\Filter\DateFilterHelper;
use PHPUnit\Framework\TestCase;

final class DateFilterHelperTest extends TestCase
{
    public function testBuildWithBothDates(): void
    {
        $result = DateFilterHelper::build('created', '2026-01-01', '2026-01-31');

        self::assertCount(2, $result);
        self::assertSame(['created', 'gte', '2026-01-01'], $result[0]);
        self::assertSame(['created', 'lte', '2026-01-31 23:59:59'], $result[1]);
    }

    public function testBuildDateToWithTimeNotAppended(): void
    {
        $result = DateFilterHelper::build('created', null, '2026-01-31 15:00:00');

        self::assertCount(1, $result);
        self::assertSame(['created', 'lte', '2026-01-31 15:00:00'], $result[0]);
    }

    public function testBuildWithNulls(): void
    {
        $result = DateFilterHelper::build('created', null, null);
        self::assertSame([], $result);
    }

    public function testBuildNormalizesIso8601(): void
    {
        $result = DateFilterHelper::build('time', '2026-01-01T10:00:00', null);

        self::assertSame(['time', 'gte', '2026-01-01 10:00:00'], $result[0]);
    }
}
