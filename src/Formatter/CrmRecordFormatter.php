<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class CrmRecordFormatter
{
    private const KNOWN_KEYS = [
        'name', 'title', 'type', 'user', 'contact', 'account', 'ticket',
        'status', 'stage', 'created', 'edited', 'description',
    ];

    /**
     * @param array<string, mixed> $record
     */
    public static function format(array $record, ?string $baseUrl = null, bool $detail = false): string
    {
        $name = $record['name'] ?? '?';
        $title = $record['title'] ?? '';
        $type = FormatterHelper::extractName($record['type'] ?? null);
        $stage = FormatterHelper::extractName($record['stage'] ?? null);
        $status = FormatterHelper::extractName($record['status'] ?? null);
        $user = FormatterHelper::extractName($record['user'] ?? null);
        $contact = FormatterHelper::extractName($record['contact'] ?? null);
        $account = FormatterHelper::extractName($record['account'] ?? null);
        $ticket = FormatterHelper::extractName($record['ticket'] ?? null);
        $ticketId = FormatterHelper::extractId($record['ticket'] ?? null);
        $created = $record['created'] ?? '';
        $edited = $record['edited'] ?? '';
        $rawDesc = (string) ($record['description'] ?? '');
        $description = $detail ? trim($rawDesc) : FormatterHelper::truncate($rawDesc);

        $ticketUrl = $ticketId !== '' ? FormatterHelper::ticketUrl($baseUrl, $ticketId) : null;

        $lines = ["**{$name}**"];
        if ($title !== '') {
            $lines[0] .= " - {$title}";
        }
        if ($type !== '') {
            $lines[] = "  Type: {$type}";
        }
        if ($stage !== '') {
            $lines[] = "  Stage: {$stage}";
        }
        if ($status !== '') {
            $lines[] = "  Status: {$status}";
        }
        if ($user !== '') {
            $lines[] = "  Owner: {$user}";
        }
        if ($contact !== '') {
            $lines[] = "  Contact: {$contact}";
        }
        if ($account !== '') {
            $lines[] = "  Account: {$account}";
        }
        if ($ticket !== '') {
            $ticketDisplay = FormatterHelper::linkedName($ticket, $ticketUrl);
            $lines[] = "  Ticket: {$ticketDisplay}";
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
    public static function formatList(array $records, int $total, int $skip, int $take, ?string $baseUrl = null): string
    {
        if ($records === []) {
            return 'No CRM records found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} CRM records:\n";
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
}
