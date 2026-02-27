<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class ActivityFormatter
{
    private const KNOWN_KEYS = [
        'name', 'type', 'action', 'queue', 'user', 'ticket', 'contact',
        'direction', 'time', 'title', 'duration', 'time_open', 'time_close',
        'description',
    ];

    /**
     * @param array<string, mixed> $activity
     */
    public static function format(array $activity, ?string $baseUrl = null, bool $detail = false): string
    {
        $name = $activity['name'] ?? '?';
        $actType = FormatterHelper::extractName($activity['type'] ?? null);
        $action = FormatterHelper::extractName($activity['action'] ?? null);
        $queue = FormatterHelper::extractName($activity['queue'] ?? null);
        $user = FormatterHelper::extractName($activity['user'] ?? null);
        $ticket = FormatterHelper::extractName($activity['ticket'] ?? null);
        $ticketId = FormatterHelper::extractId($activity['ticket'] ?? null);
        $contact = FormatterHelper::extractName($activity['contact'] ?? null);
        $direction = $activity['direction'] ?? '';
        $time = $activity['time'] ?? '';
        $title = $activity['title'] ?? '';
        $duration = $activity['duration'] ?? null;
        $timeOpen = $activity['time_open'] ?? '';
        $timeClose = $activity['time_close'] ?? '';
        $rawDesc = (string) ($activity['description'] ?? '');
        $description = $detail ? trim($rawDesc) : FormatterHelper::truncate($rawDesc, 500);

        $ticketUrl = $ticketId !== '' ? FormatterHelper::ticketUrl($baseUrl, $ticketId) : null;

        $lines = ["**{$name}**"];
        if ($title !== '') {
            $lines[0] .= " - {$title}";
        }
        if ($actType !== '' || $action !== '') {
            $parts = [];
            if ($actType !== '') {
                $parts[] = $actType;
            }
            if ($action !== '') {
                $parts[] = "status={$action}";
            }
            $lines[] = '  Type: ' . implode(' | ', $parts);
        }
        if ($direction !== '') {
            $lines[] = "  Direction: {$direction}";
        }
        if ($queue !== '') {
            $lines[] = "  Queue: {$queue}";
        }
        if ($user !== '') {
            $lines[] = "  Agent: {$user}";
        }
        if ($ticket !== '') {
            $ticketDisplay = FormatterHelper::linkedName($ticket, $ticketUrl);
            $lines[] = "  Ticket: {$ticketDisplay}";
        }
        if ($contact !== '') {
            $lines[] = "  Contact: {$contact}";
        }
        if ($time !== '') {
            $lines[] = "  Time: {$time}";
        }
        if ($duration !== null && $duration !== '') {
            $lines[] = "  Duration: {$duration}s";
        }
        if ($timeOpen !== '') {
            $lines[] = "  Opened: {$timeOpen}";
        }
        if ($timeClose !== '') {
            $lines[] = "  Closed: {$timeClose}";
        }
        if ($description !== '') {
            $lines[] = "  Content: {$description}";
        }

        array_push($lines, ...FormatterHelper::formatCustomFields($activity));
        array_push($lines, ...FormatterHelper::formatExtraFields($activity, self::KNOWN_KEYS));

        return implode("\n", $lines);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public static function formatList(array $records, int $total, int $skip, int $take, ?string $baseUrl = null): string
    {
        if ($records === []) {
            return 'No activities found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} activities:\n";
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
