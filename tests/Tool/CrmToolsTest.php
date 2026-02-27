<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\CrmTools;
use PHPUnit\Framework\TestCase;

final class CrmToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private CrmTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new CrmTools($this->client);
    }

    public function testListCrmRecordsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'crm1', 'title' => 'Big Deal', 'type' => ['name' => 'deal']],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listCrmRecords();
        self::assertFalse($result->isError);
        self::assertStringContainsString('Big Deal', self::resultText($result));
    }

    public function testListCrmRecordsValidatesSortDir(): void
    {
        $result = $this->tools->listCrmRecords(sort_dir: 'up');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid sort direction', self::resultText($result));
    }

    public function testListCrmRecordsValidatesDate(): void
    {
        $result = $this->tools->listCrmRecords(date_from: 'invalid');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid date format', self::resultText($result));
    }

    public function testGetCrmRecordReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => 'crm1',
            'title' => 'Big Deal',
            'type' => ['name' => 'deal'],
            'description' => 'A big deal worth pursuing',
        ]);

        $result = $this->tools->getCrmRecord('crm1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('Big Deal', self::resultText($result));
    }

    public function testGetCrmRecordNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getCrmRecord('nonexistent');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }
}
