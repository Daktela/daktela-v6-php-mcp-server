<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class AccountFormatter
{
    private const KNOWN_KEYS = [
        'name', 'title', 'user', 'description', 'sla', 'created', 'edited',
    ];

    /**
     * @param array<string, mixed> $record
     */
    public static function format(array $record, bool $detail = false): string
    {
        $name = $record['name'] ?? '?';
        $title = $record['title'] ?? '';
        $user = FormatterHelper::extractName($record['user'] ?? null);
        $sla = FormatterHelper::extractName($record['sla'] ?? null);
        $created = $record['created'] ?? '';
        $edited = $record['edited'] ?? '';
        $rawDesc = (string) ($record['description'] ?? '');
        $description = $detail ? trim($rawDesc) : FormatterHelper::truncate($rawDesc);

        $lines = ["**{$name}**"];
        if ($title !== '') {
            $lines[0] .= " - {$title}";
        }
        if ($user !== '') {
            $lines[] = "  Owner: {$user}";
        }
        if ($sla !== '') {
            $lines[] = "  SLA: {$sla}";
        }
        if ($created !== '') {
            $lines[] = "  Created: {$created}";
        }
        if ($edited !== '') {
            $lines[] = "  Last edited: {$edited}";
        }
        if ($description !== '') {
            $lines[] = "  Description: {$description}";
        }

        array_push($lines, ...FormatterHelper::formatCustomFields($record));
        array_push($lines, ...FormatterHelper::formatExtraFields($record, self::KNOWN_KEYS));

        return implode("\n", $lines);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public static function formatList(array $records, int $total, int $skip, int $take): string
    {
        if ($records === []) {
            return 'No accounts found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} accounts:\n";
        $body = implode("\n\n", array_map(
            fn(array $r) => self::format($r),
            $records,
        ));
        $footer = '';
        if ($end < $total) {
            $footer = "\n\n(Use skip={$end} to see next page)";
        }

        return $header . $body . $footer;
    }
}
