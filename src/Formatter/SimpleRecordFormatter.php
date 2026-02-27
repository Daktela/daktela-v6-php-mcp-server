<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class SimpleRecordFormatter
{
    /**
     * Format a simple record (queue, user, category) with name, title, and key metadata.
     *
     * @param array<string, mixed> $record
     */
    public static function format(array $record): string
    {
        $name = $record['name'] ?? '?';
        $title = $record['title'] ?? '';
        $type = $record['type'] ?? '';
        $email = $record['email'] ?? '';
        $description = FormatterHelper::truncate($record['description'] ?? null, 100);

        $line = "**{$name}**";
        if ($title !== '') {
            $line .= " - {$title}";
        }
        if ($type !== '') {
            $line .= " [{$type}]";
        }
        if ($email !== '') {
            $line .= " <{$email}>";
        }
        if ($description !== '') {
            $line .= " ({$description})";
        }

        return $line;
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public static function formatList(array $records, int $total, int $skip, int $take, string $entity): string
    {
        if ($records === []) {
            return "No {$entity} found.";
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} {$entity}:\n";
        $body = implode("\n", array_map(self::format(...), $records));
        $footer = '';
        if ($end < $total) {
            $footer = "\n\n(Use skip={$end} to see next page)";
        }

        return $header . $body . $footer;
    }
}
