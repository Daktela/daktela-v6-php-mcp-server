<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\MessagingTools;
use PHPUnit\Framework\TestCase;

final class MessagingToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private MessagingTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new MessagingTools($this->client);
    }

    public function testListChatsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'chat1', 'time' => '2026-02-25 10:00:00', 'text' => 'Hello'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listChats('webchat');
        self::assertFalse($result->isError);
        self::assertStringContainsString('1', self::resultText($result));
    }

    public function testListChatsInvalidChannel(): void
    {
        $result = $this->tools->listChats('telegram');
        self::assertFalse($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('Unknown channel', $text);
        self::assertStringContainsString('webchat', $text);
    }

    public function testListChatsWithInvalidDirection(): void
    {
        $result = $this->tools->listChats('sms', direction: 'inbound');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid direction', self::resultText($result));
    }

    public function testListChatsWebchatIgnoresDirection(): void
    {
        $this->client->expects($this->once())
            ->method('list')
            ->with(
                'activitiesWeb',
                $this->callback(function (?array $filters) {
                    if ($filters === null) {
                        return true;
                    }
                    foreach ($filters as $f) {
                        if ($f[0] === 'direction') {
                            return false;
                        }
                    }

                    return true;
                }),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
            )
            ->willReturn(['data' => [], 'total' => 0]);

        $this->tools->listChats('webchat', direction: 'in');
    }

    public function testGetChatReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => 'chat1',
            'time' => '2026-02-25',
            'text' => 'Chat content',
        ]);

        $result = $this->tools->getChat('webchat', 'chat1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('chat1', self::resultText($result));
    }

    public function testGetChatInvalidChannel(): void
    {
        $result = $this->tools->getChat('telegram', 'chat1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('Unknown channel', self::resultText($result));
    }

    public function testGetChatNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getChat('sms', 'nonexistent');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }
}
