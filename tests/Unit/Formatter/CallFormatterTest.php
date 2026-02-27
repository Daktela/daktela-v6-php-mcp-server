<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Formatter;

use Daktela\McpServer\Formatter\CallFormatter;
use PHPUnit\Framework\TestCase;

final class CallFormatterTest extends TestCase
{
    public function testFormatMinimalCall(): void
    {
        $call = ['id_call' => '100', 'call_time' => '2026-01-15 14:30:00'];
        $result = CallFormatter::format($call);

        self::assertStringContainsString('**Call 100**', $result);
        self::assertStringContainsString('Time: 2026-01-15 14:30:00', $result);
    }

    public function testFormatCallWithAllFields(): void
    {
        $call = [
            'id_call' => '200',
            'call_time' => '2026-01-15 14:30:00',
            'direction' => 'in',
            'answered' => true,
            'id_queue' => ['title' => 'Support'],
            'id_agent' => ['title' => 'John Doe'],
            'clid' => '+420123456789',
            'prefix_clid_name' => 'John',
            'duration' => '120',
            'wait_time' => '15',
            'activities' => [['name' => 'act_1', 'ticket' => ['name' => 'T-5000']]],
        ];

        $result = CallFormatter::format($call, 'https://example.daktela.com');

        self::assertStringContainsString('Direction: in', $result);
        self::assertStringContainsString('Answered: Yes', $result);
        self::assertStringContainsString('Queue: Support', $result);
        self::assertStringContainsString('Agent: John Doe', $result);
        self::assertStringContainsString('John (+420123456789)', $result);
        self::assertStringContainsString('Duration: 120s', $result);
        self::assertStringContainsString('Wait time: 15s', $result);
        self::assertStringContainsString('Ticket:', $result);
    }

    public function testFormatListEmpty(): void
    {
        self::assertSame('No calls found.', CallFormatter::formatList([], 0, 0, 50));
    }

    public function testFormatListWithPagination(): void
    {
        $records = [
            ['id_call' => '1', 'call_time' => '2026-01-15 14:30:00'],
            ['id_call' => '2', 'call_time' => '2026-01-15 14:31:00'],
        ];

        $result = CallFormatter::formatList($records, 100, 0, 50);

        self::assertStringContainsString('Showing 1-2 of 100 calls:', $result);
        self::assertStringContainsString('Use skip=2 to see next page', $result);
    }
}
