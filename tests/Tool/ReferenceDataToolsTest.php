<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaApiException;
use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\ReferenceDataTools;
use PHPUnit\Framework\TestCase;

final class ReferenceDataToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private ReferenceDataTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new ReferenceDataTools($this->client);
    }

    public function testListQueuesReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'support', 'title' => 'Support Queue'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listQueues();
        self::assertFalse($result->isError);
        self::assertStringContainsString('Support Queue', self::resultText($result));
    }

    public function testListUsersReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'john.doe', 'title' => 'John Doe'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listUsers();
        self::assertFalse($result->isError);
        self::assertStringContainsString('John Doe', self::resultText($result));
    }

    public function testListQueuesApiError(): void
    {
        $this->client->method('list')->willThrowException(
            new DaktelaApiException('queues', 500, 'Internal Server Error'),
        );

        $result = $this->tools->listQueues();
        self::assertTrue($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('API error', $text);
        self::assertStringContainsString('500', $text);
        self::assertStringContainsString('temporarily unavailable', $text);
    }

    public function testListGroupsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [['name' => 'g1', 'title' => 'Group A']],
            'total' => 1,
        ]);

        $result = $this->tools->listGroups();
        self::assertFalse($result->isError);
        self::assertStringContainsString('Group A', self::resultText($result));
    }

    public function testListStatusesReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [['name' => 's0', 'title' => 'S0-Qualify']],
            'total' => 1,
        ]);

        $result = $this->tools->listStatuses();
        self::assertFalse($result->isError);
        self::assertStringContainsString('S0-Qualify', self::resultText($result));
    }

    public function testListRealtimeSessionsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'sess1', 'user' => ['name' => 'john', 'title' => 'John'], 'state' => 'ready'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listRealtimeSessions();
        self::assertFalse($result->isError);
        self::assertStringContainsString('1', self::resultText($result));
    }

    public function testListPaginationClamped(): void
    {
        $this->client->expects($this->once())
            ->method('list')
            ->with(
                'queues',
                $this->anything(),
                0,
                250,  // clamped from 5000
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
            )
            ->willReturn(['data' => [], 'total' => 0]);

        $this->tools->listQueues(take: 5000);
    }
}
