<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\ActivityTools;
use PHPUnit\Framework\TestCase;

final class ActivityToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private ActivityTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new ActivityTools($this->client);
    }

    public function testListActivitiesReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'act1', 'type' => 'CALL', 'action' => 'CLOSE', 'time' => '2026-02-25 10:00:00'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listActivities();
        self::assertFalse($result->isError);
        self::assertStringContainsString('CALL', self::resultText($result));
    }

    public function testListActivitiesWithInvalidType(): void
    {
        $result = $this->tools->listActivities(type: 'PHONE');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid activity type', self::resultText($result));
    }

    public function testListActivitiesWithInvalidAction(): void
    {
        $result = $this->tools->listActivities(action: 'DONE');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid activity action', self::resultText($result));
    }

    public function testListActivitiesValidatesDate(): void
    {
        $result = $this->tools->listActivities(date_from: 'not-a-date');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid date format', self::resultText($result));
    }

    public function testGetActivityReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => 'act1',
            'type' => 'EMAIL',
            'action' => 'OPEN',
            'time' => '2026-02-25',
        ]);

        $result = $this->tools->getActivity('act1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('EMAIL', self::resultText($result));
    }

    public function testGetActivityNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getActivity('nonexistent');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }
}
