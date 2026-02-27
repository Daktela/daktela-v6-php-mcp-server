<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Tool\KnowledgeBaseTools;
use PHPUnit\Framework\TestCase;

final class KnowledgeBaseToolsTest extends TestCase
{
    use ToolTestHelper;

    private DaktelaClientInterface $client;
    private KnowledgeBaseTools $tools;

    protected function setUp(): void
    {
        $this->client = $this->createMock(DaktelaClientInterface::class);
        $this->client->method('getBaseUrl')->willReturn('https://test.daktela.com');
        $this->client->method('getCacheIdentity')->willReturn('test');
        $this->tools = new KnowledgeBaseTools($this->client);
    }

    public function testListArticlesReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'art1', 'title' => 'How to reset password', 'folder' => ['name' => 'faq', 'title' => 'FAQ']],
            ],
            'total' => 1,
        ]);

        $result = $this->tools->listArticles();
        self::assertFalse($result->isError);
        self::assertStringContainsString('reset password', self::resultText($result));
    }

    public function testListArticlesWithFolderNotFound(): void
    {
        // Folder resolution: get returns null, list returns empty
        $this->client->method('get')->willReturn(null);
        $this->client->method('list')->willReturn(['data' => [], 'total' => 0]);

        $result = $this->tools->listArticles(folder: 'nonexistent');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }

    public function testGetArticleReturnsDetail(): void
    {
        $this->client->method('get')->willReturn([
            'name' => 'art1',
            'title' => 'Getting Started',
            'content' => '<h1>Welcome</h1><p>This is a guide.</p>',
        ]);

        $result = $this->tools->getArticle('art1');
        self::assertFalse($result->isError);
        self::assertStringContainsString('Getting Started', self::resultText($result));
    }

    public function testGetArticleNotFound(): void
    {
        $this->client->method('get')->willReturn(null);
        $result = $this->tools->getArticle('nonexistent');
        self::assertFalse($result->isError);
        self::assertStringContainsString('not found', self::resultText($result));
    }

    public function testListArticleFoldersReturnsFormatted(): void
    {
        $this->client->method('list')->willReturn([
            'data' => [
                ['name' => 'faq', 'title' => 'FAQ', 'parent' => null],
                ['name' => 'guides', 'title' => 'Guides', 'parent' => null],
            ],
            'total' => 2,
        ]);

        $result = $this->tools->listArticleFolders();
        self::assertFalse($result->isError);
        $text = self::resultText($result);
        self::assertStringContainsString('FAQ', $text);
        self::assertStringContainsString('Guides', $text);
    }
}
