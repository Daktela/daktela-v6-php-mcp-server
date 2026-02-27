<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class CampaignRecordFormatter
{
    private const KNOWN_KEYS = [
        'name', 'user', 'record_type', 'contact', 'action', 'call_id',
        'nextcall', 'statuses', 'created', 'edited',
    ];

    private const ACTION_MAP = [
        '0' => 'Not assigned',
        '1' => 'Ready',
        '2' => 'Rescheduled by Dialer',
        '3' => 'Call in progress',
        '4' => 'Hangup',
        '5' => 'Done',
        '6' => 'Rescheduled',
    ];

    /**
     * @param array<string, mixed> $record
     */
    public static function format(array $record): string
    {
        $name = $record['name'] ?? '?';
        $user = FormatterHelper::extractName($record['user'] ?? null);
        $recordType = FormatterHelper::extractName($record['record_type'] ?? null);
        $contact = FormatterHelper::extractName($record['contact'] ?? null);
        $rawAction = $record['action'] ?? null;
        $callId = $record['call_id'] ?? '';
        $nextcall = $record['nextcall'] ?? '';
        $statuses = FormatterHelper::formatStatuses($record['statuses'] ?? null);
        $created = $record['created'] ?? '';
        $edited = $record['edited'] ?? '';

        // Map action code to human-readable label
        $action = '';
        if ($rawAction !== null && $rawAction !== '') {
            $actionStr = (string) $rawAction;
            $action = self::ACTION_MAP[$actionStr] ?? $actionStr;
        }

        $lines = ["**{$name}**"];

        if ($recordType !== '') {
            $lines[] = "  Campaign type: {$recordType}";
        }
        if ($action !== '') {
            $lines[] = "  Action: {$action}";
        }
        if ($statuses !== '') {
            $lines[] = "  Statuses: {$statuses}";
        }
        if ($user !== '') {
            $lines[] = "  Agent: {$user}";
        }
        if ($contact !== '') {
            $lines[] = "  Contact: {$contact}";
        }
        if ($callId !== '') {
            $lines[] = "  Call: {$callId}";
        }
        if ($nextcall !== '') {
            $lines[] = "  Next call: {$nextcall}";
        }
        if ($created !== '') {
            $lines[] = "  Created: {$created}";
        }
        if ($edited !== '') {
            $lines[] = "  Last edited: {$edited}";
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
            return 'No campaign records found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} campaign records:\n";
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
