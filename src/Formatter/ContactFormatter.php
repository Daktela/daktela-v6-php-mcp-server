<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class ContactFormatter
{
    private const KNOWN_KEYS = [
        'name', 'title', 'lastname', 'firstname', 'account', 'user',
        'email', 'number', 'nps_score', 'created', 'edited',
    ];

    /**
     * @param array<string, mixed> $record
     */
    public static function format(array $record): string
    {
        $name = $record['name'] ?? '?';
        $title = $record['title'] ?? '';
        $lastname = $record['lastname'] ?? '';
        $firstname = $record['firstname'] ?? '';
        $account = FormatterHelper::extractName($record['account'] ?? null);
        $user = FormatterHelper::extractName($record['user'] ?? null);
        $email = $record['email'] ?? '';
        $number = $record['number'] ?? '';
        $npsScore = $record['nps_score'] ?? null;
        $created = $record['created'] ?? '';
        $edited = $record['edited'] ?? '';

        // Build header: name + fullname from firstname + title/lastname
        $fullName = trim("{$firstname} " . ($title !== '' ? $title : $lastname));
        $header = "**{$name}**";
        if ($fullName !== '') {
            $header .= " - {$fullName}";
        }

        $lines = [$header];

        if ($account !== '') {
            $lines[] = "  Account: {$account}";
        }
        if ($user !== '') {
            $lines[] = "  Owner: {$user}";
        }
        if ($email !== '') {
            $lines[] = "  Email: {$email}";
        }
        if ($number !== '') {
            $lines[] = "  Phone: {$number}";
        }
        if ($npsScore !== null && $npsScore !== '') {
            $lines[] = "  NPS score: {$npsScore}";
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
            return 'No contacts found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} contacts:\n";
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
