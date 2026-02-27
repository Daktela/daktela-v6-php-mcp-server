<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Formatter;

use Daktela\McpServer\Formatter\FormatterHelper;
use PHPUnit\Framework\TestCase;

final class FormatterHelperTest extends TestCase
{
    public function testExtractNameFromNull(): void
    {
        self::assertSame('', FormatterHelper::extractName(null));
    }

    public function testExtractNameFromString(): void
    {
        self::assertSame('john', FormatterHelper::extractName('john'));
    }

    public function testExtractNameFromDictWithTitle(): void
    {
        self::assertSame('John Doe', FormatterHelper::extractName(['title' => 'John Doe', 'name' => 'john']));
    }

    public function testExtractNameFromDictWithNameOnly(): void
    {
        self::assertSame('john', FormatterHelper::extractName(['name' => 'john']));
    }

    public function testExtractIdFromNull(): void
    {
        self::assertSame('', FormatterHelper::extractId(null));
    }

    public function testExtractIdFromString(): void
    {
        self::assertSame('123', FormatterHelper::extractId('123'));
    }

    public function testExtractIdFromDict(): void
    {
        self::assertSame('john', FormatterHelper::extractId(['name' => 'john', 'title' => 'John Doe']));
    }

    public function testTruncate(): void
    {
        self::assertSame('', FormatterHelper::truncate(null));
        self::assertSame('', FormatterHelper::truncate(''));
        self::assertSame('short', FormatterHelper::truncate('short'));
        self::assertSame('abc...', FormatterHelper::truncate('abcdef', 3));
    }

    public function testFormatStatuses(): void
    {
        self::assertSame('', FormatterHelper::formatStatuses(null));
        self::assertSame('', FormatterHelper::formatStatuses([]));
        self::assertSame('Active, Pending', FormatterHelper::formatStatuses([
            ['title' => 'Active'],
            ['title' => 'Pending'],
        ]));
    }

    public function testReadableLabel(): void
    {
        self::assertSame('Lead type', FormatterHelper::readableLabel('lead_type'));
        self::assertSame('Lead Type', FormatterHelper::readableLabel('leadType'));
    }

    public function testFormatValueNull(): void
    {
        self::assertNull(FormatterHelper::formatValue(null));
        self::assertNull(FormatterHelper::formatValue(''));
    }

    public function testFormatValueBool(): void
    {
        self::assertSame('Yes', FormatterHelper::formatValue(true));
        self::assertSame('No', FormatterHelper::formatValue(false));
    }

    public function testFormatValueArray(): void
    {
        self::assertNull(FormatterHelper::formatValue([]));
        self::assertSame('A, B', FormatterHelper::formatValue([
            ['title' => 'A'],
            ['title' => 'B'],
        ]));
    }

    public function testTicketUrl(): void
    {
        self::assertNull(FormatterHelper::ticketUrl(null, '123'));
        self::assertNull(FormatterHelper::ticketUrl('', '123'));
        self::assertSame(
            'https://example.daktela.com/tickets/update/123',
            FormatterHelper::ticketUrl('https://example.daktela.com/', '123'),
        );
    }

    public function testLinkedName(): void
    {
        self::assertSame('123', FormatterHelper::linkedName('123', null));
        self::assertSame('[123](https://example.com)', FormatterHelper::linkedName('123', 'https://example.com'));
    }

    public function testExtractTicketFromActivities(): void
    {
        self::assertNull(FormatterHelper::extractTicketFromActivities(null));
        self::assertNull(FormatterHelper::extractTicketFromActivities([]));
        self::assertSame('12345', FormatterHelper::extractTicketFromActivities([
            ['ticket' => ['name' => '12345']],
        ]));
    }

    public function testFormatCustomFields(): void
    {
        $record = ['customFields' => ['lead_source' => 'Web', 'empty' => null]];
        $result = FormatterHelper::formatCustomFields($record);
        self::assertSame(['  Lead source: Web'], $result);
    }

    public function testFormatExtraFields(): void
    {
        $record = ['name' => 'test', 'unknown_field' => 'value', '_private' => 'skip'];
        $result = FormatterHelper::formatExtraFields($record, ['name']);
        self::assertSame(['  Unknown field: value'], $result);
    }
}
