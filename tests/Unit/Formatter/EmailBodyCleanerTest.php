<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Formatter;

use Daktela\McpServer\Formatter\EmailBodyCleaner;
use PHPUnit\Framework\TestCase;

final class EmailBodyCleanerTest extends TestCase
{
    public function testStripHtmlTags(): void
    {
        $html = '<p>Hello <b>world</b></p>';
        $result = EmailBodyCleaner::clean($html);
        self::assertStringContainsString('Hello world', $result);
        self::assertStringNotContainsString('<p>', $result);
        self::assertStringNotContainsString('<b>', $result);
    }

    public function testStripStyleAndScript(): void
    {
        $html = '<style>.foo { color: red; }</style><script>alert("xss")</script>Hello';
        $result = EmailBodyCleaner::clean($html);
        self::assertSame('Hello', $result);
    }

    public function testRemoveQuotedReplies(): void
    {
        $html = "My reply\n\nOn Monday, Jan 1 2026, John wrote:\nOriginal message here";
        $result = EmailBodyCleaner::clean($html);
        self::assertStringContainsString('My reply', $result);
        self::assertStringNotContainsString('Original message', $result);
    }

    public function testRemoveSignature(): void
    {
        $html = "Message body\n-- \nJohn Doe\nCompany Inc.";
        $result = EmailBodyCleaner::clean($html);
        self::assertStringContainsString('Message body', $result);
        self::assertStringNotContainsString('Company Inc.', $result);
    }

    public function testRemoveQuotedLines(): void
    {
        $html = "My reply\n> Quoted line\n> Another quoted line\nNormal line";
        $result = EmailBodyCleaner::clean($html);
        self::assertStringContainsString('My reply', $result);
        self::assertStringContainsString('Normal line', $result);
        self::assertStringNotContainsString('Quoted line', $result);
    }

    public function testTruncation(): void
    {
        $long = str_repeat('a', 2000);
        $result = EmailBodyCleaner::clean($long, 100);
        self::assertLessThanOrEqual(104, \strlen($result)); // 100 + "..."
        self::assertStringEndsWith('...', $result);
    }

    public function testDecodeHtmlEntities(): void
    {
        $html = 'Caf&eacute; &amp; bar';
        $result = EmailBodyCleaner::clean($html);
        self::assertStringContainsString('Café & bar', $result);
    }

    public function testEmptyInput(): void
    {
        self::assertSame('', EmailBodyCleaner::clean(''));
    }
}
