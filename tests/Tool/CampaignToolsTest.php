<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\CampaignTools;
use PHPUnit\Framework\TestCase;

final class CampaignToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private CampaignTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new CampaignTools($this->client);
    }

    public function testListCampaignRecordsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'cr1', 'title' => 'Spring Campaign', 'record_type' => ['name' => 'outbound']],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listCampaignRecords();
        self::assertFalse($result->isError);
        self::assertStringContainsString('Spring Campaign', self::resultText($result));
    }

    public function testListCampaignRecordsValidatesSortDir(): void
    {
        $result = $this->tools->listCampaignRecords(sort_dir: 'sideways');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid sort direction', self::resultText($result));
    }

    public function testListCampaignTypesReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'outbound', 'title' => 'Outbound'],
                ['name' => 'survey', 'title' => 'Survey'],
            ],
            'total' => 2,
        ]);

        $result = $this->tools->listCampaignTypes();
        self::assertFalse($result->isError);
        self::assertStringContainsString('Outbound', self::resultText($result));
    }

    public function testGetCampaignRecordReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => 'cr1',
            'record_type' => ['name' => 'outbound'],
            'action' => '5',
        ]);

        $result = $this->tools->getCampaignRecord('cr1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('cr1', self::resultText($result));
    }

    public function testGetCampaignRecordNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getCampaignRecord('nonexistent');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }
}
