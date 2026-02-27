<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

use League\HTMLToMarkdown\HtmlConverter;

final class ArticleFormatter
{
    private const KNOWN_KEYS = [
        'name', 'title', 'folder', 'tags', 'description', 'content',
        'created', 'edited', 'seen_count', 'published',
    ];

    /**
     * @param array<string, mixed> $record
     */
    public static function format(array $record, ?string $baseUrl = null, bool $detail = false): string
    {
        $name = $record['name'] ?? '?';
        $title = $record['title'] ?? '';
        $folder = FormatterHelper::extractName($record['folder'] ?? null);
        $tags = self::formatTags($record['tags'] ?? null);
        $description = $record['description'] ?? '';
        $content = (string) ($record['content'] ?? '');
        $created = $record['created'] ?? '';
        $edited = $record['edited'] ?? '';
        $seenCount = $record['seen_count'] ?? null;
        $published = $record['published'] ?? null;

        $lines = ["**{$name}** - {$title}"];

        if ($detail) {
            $url = rtrim($baseUrl ?? '', '/') . '/articles/update/' . $name;
            $lines[] = "  URL: {$url}";

            if ($folder !== '') {
                $lines[] = "  Folder: {$folder}";
            }
            if ($tags !== '') {
                $lines[] = "  Tags: {$tags}";
            }
            if ($created !== '') {
                $lines[] = "  Created: {$created}";
            }
            if ($edited !== '') {
                $lines[] = "  Edited: {$edited}";
            }
            if ($seenCount !== null && $seenCount !== '') {
                $lines[] = "  Views: {$seenCount}";
            }
            if ($published !== null) {
                $lines[] = '  Published: ' . ($published ? 'Yes' : 'No');
            }

            if ($content !== '') {
                $markdown = self::htmlToMarkdown($content, $baseUrl);
                $lines[] = "  Content:\n{$markdown}";
            } elseif ($description !== '') {
                $lines[] = "  Description: {$description}";
            }
        } else {
            if ($folder !== '') {
                $lines[] = "  Folder: {$folder}";
            }
            if ($tags !== '') {
                $lines[] = "  Tags: {$tags}";
            }
            if ($created !== '') {
                $lines[] = "  Created: {$created}";
            }
            if ($edited !== '') {
                $lines[] = "  Edited: {$edited}";
            }
            if ($description !== '') {
                $lines[] = '  Description: ' . FormatterHelper::truncate($description);
            }
        }

        array_push($lines, ...FormatterHelper::formatCustomFields($record));
        array_push($lines, ...FormatterHelper::formatExtraFields($record, self::KNOWN_KEYS));

        return implode("\n", $lines);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public static function formatList(array $records, int $total, int $skip, int $take, ?string $baseUrl = null): string
    {
        if ($records === []) {
            return 'No articles found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} articles:\n";
        $body = implode("\n\n", array_map(
            fn(array $r) => self::format($r, baseUrl: $baseUrl),
            $records,
        ));
        $footer = '';
        if ($end < $total) {
            $footer = "\n\n(Use skip={$end} to see next page)";
        }

        return $header . $body . $footer;
    }

    /**
     * Build a visual indented tree from a flat list of folders.
     *
     * Each folder has: name, title, parent (dict/string/null), article_count.
     *
     * @param list<array<string, mixed>> $folders
     */
    public static function formatFolderTree(array $folders): string
    {
        if ($folders === []) {
            return 'No article folders found.';
        }

        // Build parent-child mapping
        /** @var array<string, list<array<string, mixed>>> $children */
        $children = [];
        /** @var list<array<string, mixed>> $roots */
        $roots = [];

        foreach ($folders as $folder) {
            $name = $folder['name'] ?? '';
            $parentObj = $folder['parent'] ?? null;
            $parentId = '';

            if (\is_array($parentObj)) {
                $parentId = (string) ($parentObj['name'] ?? '');
            } elseif (\is_string($parentObj) && $parentObj !== '') {
                $parentId = $parentObj;
            }

            if ($parentId === '') {
                $roots[] = $folder;
            } else {
                $children[$parentId][] = $folder;
            }
        }

        $lines = ['Article folders (' . \count($folders) . " total):\n"];
        self::renderTree($lines, $roots, $children, 0);

        return implode("\n", $lines);
    }

    /**
     * @param list<string> $lines
     * @param list<array<string, mixed>> $nodes
     * @param array<string, list<array<string, mixed>>> $children
     */
    private static function renderTree(array &$lines, array $nodes, array $children, int $depth): void
    {
        foreach ($nodes as $node) {
            $name = $node['name'] ?? '?';
            $title = $node['title'] ?? '';
            $articleCount = $node['article_count'] ?? 0;

            $indent = str_repeat('  ', $depth);
            $display = $title !== '' ? "{$name} - {$title}" : $name;
            $lines[] = "{$indent}- **{$display}** ({$articleCount} articles)";

            if (isset($children[$name])) {
                self::renderTree($lines, $children[$name], $children, $depth + 1);
            }
        }
    }

    private static function formatTags(mixed $tags): string
    {
        if (empty($tags)) {
            return '';
        }

        if (\is_array($tags) && array_is_list($tags)) {
            $names = array_filter(array_map(
                fn(mixed $t) => FormatterHelper::extractName($t),
                $tags,
            ));

            return implode(', ', $names);
        }

        return FormatterHelper::extractName($tags);
    }

    private static function htmlToMarkdown(string $html, ?string $baseUrl): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Strip style and script blocks
        $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $html);
        $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html);

        // Prepend base URL to relative href and src attributes
        if ($baseUrl !== null && $baseUrl !== '') {
            $base = rtrim($baseUrl, '/');
            $html = (string) preg_replace(
                '/(<(?:a|img|link|source)\b[^>]*\s(?:href|src))="(?!https?:\/\/|mailto:|tel:|#)([^"]*)"/',
                '$1="' . $base . '/$2"',
                $html,
            );
        }

        $converter = new HtmlConverter(['heading_style' => 'atx', 'strip_tags' => true]);

        return $converter->convert($html);
    }
}
