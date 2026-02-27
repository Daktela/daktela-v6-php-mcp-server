<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Filter;

use Daktela\McpServer\Filter\SortFieldValidator;
use PHPUnit\Framework\TestCase;

final class SortFieldValidatorTest extends TestCase
{
    public function testValidSortField(): void
    {
        self::assertSame('created', SortFieldValidator::validate('tickets', 'created'));
    }

    public function testInvalidSortFieldReturnsNull(): void
    {
        self::assertNull(SortFieldValidator::validate('tickets', 'invalid_field'));
    }

    public function testNullSortReturnsNull(): void
    {
        self::assertNull(SortFieldValidator::validate('tickets', null));
    }

    public function testUnknownEndpointPassesThrough(): void
    {
        self::assertSame('anything', SortFieldValidator::validate('unknownEndpoint', 'anything'));
    }

    public function testCallSortFields(): void
    {
        self::assertSame('call_time', SortFieldValidator::validate('activitiesCall', 'call_time'));
        self::assertNull(SortFieldValidator::validate('activitiesCall', 'created'));
    }
}
