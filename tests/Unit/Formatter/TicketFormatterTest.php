<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Formatter;

use Daktela\McpServer\Formatter\TicketFormatter;
use PHPUnit\Framework\TestCase;

final class TicketFormatterTest extends TestCase
{
    public function testFormatMinimalTicket(): void
    {
        $ticket = ['name' => '12345', 'title' => 'Test ticket'];
        $result = TicketFormatter::format($ticket);

        self::assertStringContainsString('**12345** - Test ticket', $result);
    }

    public function testFormatTicketWithBaseUrl(): void
    {
        $ticket = ['name' => '12345', 'title' => 'Test'];
        $result = TicketFormatter::format($ticket, baseUrl: 'https://example.daktela.com');

        self::assertStringContainsString('[12345](https://example.daktela.com/tickets/update/12345)', $result);
        self::assertStringContainsString('Link: https://example.daktela.com/tickets/update/12345', $result);
    }

    public function testFormatTicketWithAllFields(): void
    {
        $ticket = [
            'name' => '12345',
            'title' => 'Test ticket',
            'stage' => 'OPEN',
            'priority' => 'HIGH',
            'category' => ['title' => 'Support'],
            'user' => ['title' => 'John Doe'],
            'contact' => ['title' => 'Jane Client'],
            'created' => '2026-01-01 10:00:00',
            'edited' => '2026-01-02 11:00:00',
        ];

        $result = TicketFormatter::format($ticket);

        self::assertStringContainsString('Stage: OPEN | priority=HIGH', $result);
        self::assertStringContainsString('Category: Support', $result);
        self::assertStringContainsString('Assigned to: John Doe', $result);
        self::assertStringContainsString('Contact: Jane Client', $result);
    }

    public function testFormatListEmpty(): void
    {
        self::assertSame('No tickets found.', TicketFormatter::formatList([], 0, 0, 50));
    }

    public function testFormatListWithPagination(): void
    {
        $records = [
            ['name' => '1', 'title' => 'Ticket 1'],
            ['name' => '2', 'title' => 'Ticket 2'],
        ];

        $result = TicketFormatter::formatList($records, 100, 0, 50);

        self::assertStringContainsString('Showing 1-2 of 100 tickets.', $result);
        self::assertStringContainsString('Use skip=2 to see next page', $result);
    }

    public function testFormatListNoMorePages(): void
    {
        $records = [
            ['name' => '1', 'title' => 'Ticket 1'],
        ];

        $result = TicketFormatter::formatList($records, 1, 0, 50);

        self::assertStringNotContainsString('next page', $result);
    }

    public function testFormatWithDescription(): void
    {
        $ticket = ['name' => '1', 'title' => 'Test', 'description' => 'Full description here'];
        $result = TicketFormatter::format($ticket);
        self::assertStringContainsString('Description: Full description here', $result);
    }

    public function testFormatDetailDoesNotTruncateDescription(): void
    {
        $longDesc = str_repeat('a', 500);
        $ticket = ['name' => '1', 'title' => 'Test', 'description' => $longDesc];

        $result = TicketFormatter::format($ticket, detail: true);
        self::assertStringContainsString($longDesc, $result);

        $result2 = TicketFormatter::format($ticket, detail: false);
        self::assertStringContainsString('...', $result2);
    }
}
