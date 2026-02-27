<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\ContactTools;
use PHPUnit\Framework\TestCase;

final class ContactToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private ContactTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new ContactTools($this->client);
    }

    public function testListContactsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'c1', 'title' => 'John Doe', 'email' => 'john@example.com'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listContacts();
        self::assertFalse($result->isError);
        self::assertStringContainsString('John Doe', self::resultText($result));
    }

    public function testListContactsValidatesDate(): void
    {
        $result = $this->tools->listContacts(date_from: 'bad-date');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid date format', self::resultText($result));
    }

    public function testGetContactReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => 'c1',
            'title' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $result = $this->tools->getContact('c1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('Jane Smith', self::resultText($result));
    }

    public function testGetContactNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getContact('c999');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }
}
