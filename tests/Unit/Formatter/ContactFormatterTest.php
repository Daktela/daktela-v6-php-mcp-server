<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Formatter;

use Daktela\McpServer\Formatter\ContactFormatter;
use PHPUnit\Framework\TestCase;

final class ContactFormatterTest extends TestCase
{
    public function testFormatMinimalContact(): void
    {
        $contact = ['name' => 'contact_123'];
        $result = ContactFormatter::format($contact);

        self::assertStringContainsString('**contact_123**', $result);
    }

    public function testFormatContactWithAllFields(): void
    {
        $contact = [
            'name' => 'contact_123',
            'title' => 'Doe',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'account' => ['title' => 'Acme Corp'],
            'user' => ['title' => 'Agent Smith'],
            'email' => 'john@example.com',
            'number' => '+420123456789',
            'nps_score' => '9',
            'created' => '2026-01-01 10:00:00',
            'edited' => '2026-01-02 11:00:00',
        ];

        $result = ContactFormatter::format($contact);

        self::assertStringContainsString('John Doe', $result);
        self::assertStringContainsString('Account: Acme Corp', $result);
        self::assertStringContainsString('Owner: Agent Smith', $result);
        self::assertStringContainsString('Email: john@example.com', $result);
        self::assertStringContainsString('Phone: +420123456789', $result);
        self::assertStringContainsString('NPS score: 9', $result);
    }

    public function testFormatListEmpty(): void
    {
        self::assertSame('No contacts found.', ContactFormatter::formatList([], 0, 0, 50));
    }

    public function testFormatListWithPagination(): void
    {
        $records = [
            ['name' => 'contact_1', 'firstname' => 'John'],
            ['name' => 'contact_2', 'firstname' => 'Jane'],
        ];

        $result = ContactFormatter::formatList($records, 50, 0, 50);

        self::assertStringContainsString('Showing 1-2 of 50 contacts:', $result);
    }
}
