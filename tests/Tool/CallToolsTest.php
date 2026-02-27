<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\CallTools;
use PHPUnit\Framework\TestCase;

final class CallToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private CallTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new CallTools($this->client);
    }

    public function testListCallsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['id_call' => '1', 'call_time' => '2026-02-25 10:00:00', 'direction' => 'in', 'answered' => true, 'duration' => 120],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listCalls();
        self::assertFalse($result->isError);
        self::assertStringContainsString('1', self::resultText($result));
    }

    public function testListCallsWithInvalidDirection(): void
    {
        $result = $this->tools->listCalls(direction: 'inbound');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid direction', self::resultText($result));
    }

    public function testListCallsNormalizesDirection(): void
    {
        $this->client->expects($this->once())
            ->method('list')
            ->with(
                'activitiesCall',
                $this->callback(function (array $filters) {
                    foreach ($filters as $f) {
                        if ($f[0] === 'direction' && $f[2] === 'in') {
                            return true;
                        }
                    }

                    return false;
                }),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
            )
            ->willReturn(['data' => [], 'total' => 0]);

        $this->tools->listCalls(direction: 'IN');
    }

    public function testGetCallReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'id_call' => '1',
            'call_time' => '2026-02-25',
            'direction' => 'in',
            'answered' => true,
            'duration' => 300,
        ]);

        $result = $this->tools->getCall('1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('300', self::resultText($result));
    }

    public function testGetCallNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getCall('999');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }

    public function testGetCallTranscriptReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['text' => 'Hello, how can I help?', 'type' => 'agent', 'start' => 0, 'end' => 5],
                ['text' => 'I have a problem.', 'type' => 'customer', 'start' => 5, 'end' => 10],
            ],
            'total' => 2,
        ]);

        $result = $this->tools->getCallTranscript('act_123');
        self::assertFalse($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('Hello', $text);
        self::assertStringContainsString('problem', $text);
    }

    public function testGetCallTranscriptNotFound(): void
    {
        $this->client->method('list')->willReturn(['data' => [], 'total' => 0]);
        $result = $this->tools->getCallTranscript('act_999');
        self::assertFalse($result->isError);
        self::assertStringContainsString('No transcript found', self::resultText($result));
    }
}
