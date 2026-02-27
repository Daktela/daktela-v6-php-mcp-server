<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\EmailTools;
use PHPUnit\Framework\TestCase;

final class EmailToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private EmailTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new EmailTools($this->client);
    }

    public function testListEmailsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'e1', 'title' => 'Re: Support', 'direction' => 'in', 'time' => '2026-02-25 10:00:00'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listEmails();
        self::assertFalse($result->isError);
        self::assertStringContainsString('Support', self::resultText($result));
    }

    public function testListEmailsWithInvalidDirection(): void
    {
        $result = $this->tools->listEmails(direction: 'inbound');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid direction', self::resultText($result));
    }

    public function testListEmailsNormalizesDirection(): void
    {
        $this->client->expects($this->once())
            ->method('list')
            ->with(
                'activitiesEmail',
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

        $this->tools->listEmails(direction: 'IN');
    }

    public function testGetEmailReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => 'e1',
            'title' => 'Re: Help needed',
            'direction' => 'in',
            'text' => '<p>Please help</p>',
        ]);

        $result = $this->tools->getEmail('e1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('Help needed', self::resultText($result));
    }

    public function testGetEmailNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getEmail('nonexistent');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }
}
