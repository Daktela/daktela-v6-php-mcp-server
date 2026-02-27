<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class ChatFormatter
{
    private const KNOWN_KEYS = [
        'name', 'title', 'sender', 'direction', 'state', 'answered',
        'queue', 'user', 'contact', 'duration', 'wait_time',
        'disconnection', 'missed', 'type', 'time',
    ];

    /**
     * @param array<string, mixed> $record
     */
    public static function format(array $record, ?string $baseUrl = null, string $channel = 'chat'): string
    {
        $name = $record['name'] ?? '?';
        $title = $record['title'] ?? '';
        $sender = $record['sender'] ?? '';
        $direction = $record['direction'] ?? '';
        $state = $record['state'] ?? '';
        $answered = $record['answered'] ?? null;
        $queue = FormatterHelper::extractName($record['queue'] ?? null);
        $user = FormatterHelper::extractName($record['user'] ?? null);
        $contact = FormatterHelper::extractName($record['contact'] ?? null);
        $duration = $record['duration'] ?? null;
        $waitTime = $record['wait_time'] ?? null;
        $disconnection = $record['disconnection'] ?? '';
        $missed = $record['missed'] ?? null;
        $type = $record['type'] ?? '';
        $time = $record['time'] ?? '';

        // Extract ticket from activities for URL linking
        $activities = $record['activities'] ?? [];
        $ticketId = FormatterHelper::extractTicketFromActivities($activities);
        $ticketUrl = ($ticketId !== null) ? FormatterHelper::ticketUrl($baseUrl, $ticketId) : null;

        $lines = ["**{$name}**"];
        if ($title !== '') {
            $lines[0] .= " - {$title}";
        }
        if ($sender !== '') {
            $lines[] = "  Sender: {$sender}";
        }
        if ($direction !== '') {
            $lines[] = "  Direction: {$direction}";
        }
        if ($state !== '') {
            $lines[] = "  State: {$state}";
        }
        if ($type !== '' && $channel === 'instagram') {
            $lines[] = "  Type: {$type}";
        }
        if ($answered !== null) {
            $lines[] = '  Answered: ' . ($answered ? 'Yes' : 'No');
        }
        if (!empty($missed)) {
            $lines[] = '  Missed: yes';
        }
        if ($queue !== '') {
            $lines[] = "  Queue: {$queue}";
        }
        if ($user !== '') {
            $lines[] = "  Agent: {$user}";
        }
        if ($contact !== '') {
            $lines[] = "  Contact: {$contact}";
        }
        if ($ticketId !== null) {
            $ticketDisplay = FormatterHelper::linkedName($ticketId, $ticketUrl);
            $lines[] = "  Ticket: {$ticketDisplay}";
        }
        if ($duration !== null && $duration !== '') {
            $lines[] = "  Duration: {$duration}s";
        }
        if ($waitTime !== null && $waitTime !== '') {
            $lines[] = "  Wait time: {$waitTime}s";
        }
        if ($disconnection !== '') {
            $lines[] = "  Disconnection: {$disconnection}";
        }
        if ($time !== '') {
            $lines[] = "  Created: {$time}";
        }

        array_push($lines, ...FormatterHelper::formatCustomFields($record));
        array_push($lines, ...FormatterHelper::formatExtraFields($record, self::KNOWN_KEYS));

        return implode("\n", $lines);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public static function formatList(
        array $records,
        int $total,
        int $skip,
        int $take,
        ?string $baseUrl = null,
        string $entity = 'chats',
        string $channel = 'chat',
    ): string {
        if ($records === []) {
            return "No {$entity} found.";
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} {$entity}:\n";
        $body = implode("\n\n", array_map(
            fn(array $r) => self::format($r, baseUrl: $baseUrl, channel: $channel),
            $records,
        ));
        $footer = '';
        if ($end < $total) {
            $footer = "\n\n(Use skip={$end} to see next page)";
        }

        return $header . $body . $footer;
    }
}
