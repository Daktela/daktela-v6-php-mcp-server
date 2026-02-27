<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaApiException;
use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\TicketTools;
use PHPUnit\Framework\TestCase;

final class TicketToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private TicketTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new TicketTools($this->client);
    }

    public function testListTicketsReturnsFormattedOutput(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => '123', 'title' => 'Test ticket', 'stage' => 'OPEN', 'priority' => 'HIGH', 'user' => ['name' => 'john', 'title' => 'John Doe']],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listTickets();
        self::assertFalse($result->isError);
        self::assertStringContainsString('Test ticket', self::resultText($result));
    }

    public function testListTicketsWithInvalidStage(): void
    {
        $result = $this->tools->listTickets(stage: 'INVALID');
        self::assertTrue($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('Invalid ticket stage', $text);
        self::assertStringContainsString('OPEN, WAIT, CLOSE, ARCHIVE', $text);
    }

    public function testListTicketsWithInvalidPriority(): void
    {
        $result = $this->tools->listTickets(priority: 'URGENT');
        self::assertTrue($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('Invalid priority', $text);
        self::assertStringContainsString('LOW, MEDIUM, HIGH', $text);
    }

    public function testListTicketsWithInvalidSortDir(): void
    {
        $result = $this->tools->listTickets(sort_dir: 'up');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid sort direction', self::resultText($result));
    }

    public function testListTicketsWithInvalidDate(): void
    {
        $result = $this->tools->listTickets(date_from: '25/02/2026');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid date format', self::resultText($result));
    }

    public function testListTicketsNormalizesStage(): void
    {
        $this->client->expects($this->once())
            ->method('list')
            ->with(
                'tickets',
                $this->callback(function (array $filters) {
                    foreach ($filters as $f) {
                        if ($f[0] === 'stage' && $f[2] === 'OPEN') {
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

        $this->tools->listTickets(stage: 'open');
    }

    public function testCountTicketsReturnsCount(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [['name' => '1']],
            'total' => 42,
        ]);

        $result = $this->tools->countTickets(stage: 'OPEN');
        self::assertFalse($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('42', $text);
        self::assertStringContainsString('stage=OPEN', $text);
    }

    public function testCountTicketsWithInvalidStage(): void
    {
        $result = $this->tools->countTickets(stage: 'BOGUS');
        self::assertTrue($result->isError);
        self::assertStringContainsString('Invalid ticket stage', self::resultText($result));
    }

    public function testGetTicketReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => '123',
            'title' => 'Bug report',
            'stage' => 'OPEN',
            'priority' => 'HIGH',
            'description' => 'Something is broken',
        ]);

        $result = $this->tools->getTicket('123');
        self::assertFalse($result->isError);
        self::assertStringContainsString('Bug report', self::resultText($result));
    }

    public function testGetTicketStripsPrefix(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('tickets', '787979')
            ->willReturn(['name' => '787979', 'title' => 'Test']);

        $this->tools->getTicket('TK00787979');
    }

    public function testGetTicketNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getTicket('999');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }

    public function testGetTicketDetailIncludesActivities(): void
    {
        $this->client->method('get')->willReturn([
            'name' => '123',
            'title' => 'Test',
            'stage' => 'OPEN',
        ]);
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'act1', 'type' => 'EMAIL', 'action' => 'OPEN', 'time' => '2026-02-25 10:00:00'],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->getTicketDetail('123');
        self::assertFalse($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('Test', $text);
        self::assertStringContainsString('Activities', $text);
    }

    public function testGetTicketDetailNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getTicketDetail('999');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }

    public function testListTicketsApiError(): void
    {
        $this->client->method('list')->willThrowException(
            new DaktelaApiException('tickets', 403, 'Forbidden'),
        );

        $result = $this->tools->listTickets();
        self::assertTrue($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('API error', $text);
        self::assertStringContainsString('403', $text);
        self::assertStringContainsString('Access denied', $text);
    }

    public function testListTicketCategoriesReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'support', 'title' => 'Support'],
                ['name' => 'sales', 'title' => 'Sales'],
            ],
            'total' => 2,
        ]);

        $result = $this->tools->listTicketCategories();
        self::assertFalse($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('Support', $text);
        self::assertStringContainsString('Sales', $text);
    }

    public function testListAccountTicketsResolvesAccount(): void
    {
        // First call: get account by name
        $this->client->method('get')
            ->willReturnCallback(function (string $endpoint, string $name) {
                if ($endpoint === 'accounts' && $name === 'Acme') {
                    return ['name' => 'acme_123', 'title' => 'Acme Corp'];
                }

                return null;
            });

        // list calls: contacts then tickets
        $callCount = 0;
        $this->client->method('list')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    // contacts query
                    return ['data' => [['name' => 'contact_1']], 'total' => 1];
                }
                // tickets query
                return ['data' => [['name' => '456', 'title' => 'Acme ticket', 'stage' => 'OPEN']], 'total' => 1];
            });

        $result = $this->tools->listAccountTickets('Acme');
        self::assertFalse($result->isError);
        self::assertStringContainsString('Acme Corp', self::resultText($result));
    }

    public function testListAccountTicketsNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $this->client->method('list')->willReturn(['data' => [], 'total' => 0]);

        $result = $this->tools->listAccountTickets('NonExistent');
        self::assertFalse($result->isError);
        self::assertStringContainsString('No account found', self::resultText($result));
    }
}
