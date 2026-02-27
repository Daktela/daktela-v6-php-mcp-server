<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\AccountTools;
use PHPUnit\Framework\TestCase;

final class AccountToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private AccountTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new AccountTools($this->client);
    }

    public function testListAccountsReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'a1', 'title' => 'Acme Corp'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listAccounts();
        self::assertFalse($result->isError);
        self::assertStringContainsString('Acme Corp', self::resultText($result));
    }

    public function testGetAccountReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => 'a1',
            'title' => 'Acme Corp',
        ]);

        $result = $this->tools->getAccount('a1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('Acme Corp', self::resultText($result));
    }

    public function testGetAccountNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getAccount('a999');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }
}
