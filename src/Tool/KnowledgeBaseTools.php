<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Client\DaktelaApiException;
use Daktela\McpServer\Formatter\ArticleFormatter;
use Daktela\McpServer\Resolver\FolderResolver;
use Daktela\McpServer\Resolver\TagResolver;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class KnowledgeBaseTools extends AbstractTools
{
    /**
     * List knowledge base articles with optional filters.
     *
     * @param string|null $search Full-text search across article title and content (partial match).
     * @param string|null $folder Filter by folder name or title. The folder name is resolved automatically.
     * @param string|null $tag Filter by tag name or title. The tag name is resolved automatically.
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100).
     */
    #[McpTool(name: 'list_articles')]
    public function listArticles(
        ?string $search = null,
        ?string $folder = null,
        ?string $tag = null,
        int $skip = 0,
        int $take = 100,
    ): CallToolResult {
        $filters = [];

        if ($folder !== null) {
            $resolvedFolder = FolderResolver::resolve($this->client, $folder);
            if ($resolvedFolder === null) {
                return self::success("Folder '{$folder}' not found.");
            }
            $filters[] = ['folder', 'eq', $resolvedFolder];
        }

        if ($tag !== null) {
            $resolvedTag = TagResolver::resolve($this->client, $tag);
            if ($resolvedTag === null) {
                return self::success("Tag '{$tag}' not found.");
            }
            $filters[] = ['tags', 'eq', $resolvedTag];
        }

        try {
            $result = $this->client->list(
                'articles',
                fieldFilters: $filters !== [] ? $filters : null,
                skip: $skip,
                take: $take,
                search: $search,
            );
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        return self::success(ArticleFormatter::formatList(
            $result['data'],
            $result['total'],
            $skip,
            $take,
            $this->client->getBaseUrl(),
        ));
    }

    /**
     * Get full details of a single knowledge base article by its name/ID.
     * Returns the full article content converted from HTML to Markdown.
     *
     * @param string $name The article internal name/ID.
     */
    #[McpTool(name: 'get_article')]
    public function getArticle(string $name): CallToolResult
    {
        return $this->executeGet(
            'articles',
            $name,
            'Article',
            fn($record) => ArticleFormatter::format($record, baseUrl: $this->client->getBaseUrl(), detail: true),
        );
    }

    /**
     * List all knowledge base article folders as a hierarchical tree.
     * Shows folder structure with article counts.
     */
    #[McpTool(name: 'list_article_folders')]
    public function listArticleFolders(): CallToolResult
    {
        try {
            $result = $this->client->list('articlesFolders', take: static::MAX_TAKE);
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        return self::success(ArticleFormatter::formatFolderTree($result['data']));
    }
}
