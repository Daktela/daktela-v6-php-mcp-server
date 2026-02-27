<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class CallFormatter
{
    private const KNOWN_KEYS = [
        'id_call', 'name', 'call_time', 'direction', 'answered', 'id_queue', 'queue',
        'id_agent', 'user', 'clid', 'contact', 'prefix_clid_name', 'did',
        'waiting_time', 'wait_time', 'ringing_time', 'hold_time', 'duration',
        'disposition_cause', 'disconnection_cause', 'pressed_key', 'missed_call',
        'missed_call_time', 'missed_callback', 'attempts', 'activities',
    ];

    /**
     * @param array<string, mixed> $record
     */
    public static function format(array $record, ?string $baseUrl = null): string
    {
        $callId = $record['id_call'] ?? $record['name'] ?? '?';
        $time = $record['call_time'] ?? '';
        $direction = $record['direction'] ?? '';
        $answered = $record['answered'] ?? null;
        $queue = FormatterHelper::extractName($record['id_queue'] ?? $record['queue'] ?? null);
        $user = FormatterHelper::extractName($record['id_agent'] ?? $record['user'] ?? null);
        $clid = $record['clid'] ?? '';
        $prefixClidName = $record['prefix_clid_name'] ?? '';
        $did = $record['did'] ?? '';
        $contact = FormatterHelper::extractName($record['contact'] ?? null);
        $duration = $record['duration'] ?? null;
        $waitTime = $record['wait_time'] ?? $record['waiting_time'] ?? null;
        $ringingTime = $record['ringing_time'] ?? null;
        $holdTime = $record['hold_time'] ?? null;
        $dispositionCause = $record['disposition_cause'] ?? '';
        $disconnectionCause = $record['disconnection_cause'] ?? '';
        $pressedKey = $record['pressed_key'] ?? '';
        $missedCall = $record['missed_call'] ?? null;
        $missedCallTime = $record['missed_call_time'] ?? '';
        $missedCallback = $record['missed_callback'] ?? null;
        $attempts = $record['attempts'] ?? null;
        $activities = $record['activities'] ?? [];

        // Extract activity name from activities list
        $activityName = '';
        if (\is_array($activities) && $activities !== []) {
            foreach ($activities as $act) {
                if (\is_array($act)) {
                    $activityName = FormatterHelper::extractName($act);
                    break;
                }
            }
        }

        // Extract ticket from activities for URL linking
        $ticketId = FormatterHelper::extractTicketFromActivities($activities);
        $ticketUrl = ($ticketId !== null) ? FormatterHelper::ticketUrl($baseUrl, $ticketId) : null;

        $lines = ["**Call {$callId}**"];

        if ($activityName !== '') {
            $lines[] = "  Activity: {$activityName}";
        }
        if ($ticketId !== null) {
            $ticketDisplay = FormatterHelper::linkedName($ticketId, $ticketUrl);
            $lines[] = "  Ticket: {$ticketDisplay}";
        }
        if ($time !== '') {
            $lines[] = "  Time: {$time}";
        }
        if ($direction !== '') {
            $lines[] = "  Direction: {$direction}";
        }
        if ($answered !== null) {
            $lines[] = '  Answered: ' . ($answered ? 'Yes' : 'No');
        }
        if (!empty($missedCall)) {
            $lines[] = '  Missed call: yes';
        }
        if ($missedCallTime !== '') {
            $lines[] = "  Missed call returned: {$missedCallTime}";
        }
        if (!empty($missedCallback)) {
            $lines[] = '  Callback call: yes';
        }
        if ($clid !== '') {
            $callerDisplay = $clid;
            if ($prefixClidName !== '') {
                $callerDisplay = "{$prefixClidName} ({$clid})";
            }
            $lines[] = "  Caller ID: {$callerDisplay}";
        }
        if ($did !== '') {
            $lines[] = "  DID: {$did}";
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
        if ($duration !== null && $duration !== '') {
            $lines[] = "  Duration: {$duration}s";
        }
        if ($waitTime !== null && $waitTime !== '') {
            $lines[] = "  Wait time: {$waitTime}s";
        }
        if ($ringingTime !== null && $ringingTime !== '') {
            $lines[] = "  Ringing time: {$ringingTime}s";
        }
        if ($holdTime !== null && $holdTime !== '') {
            $lines[] = "  Hold time: {$holdTime}s";
        }
        if ($dispositionCause !== '') {
            $lines[] = "  Disposition: {$dispositionCause}";
        }
        if ($disconnectionCause !== '') {
            $lines[] = "  Disconnection: {$disconnectionCause}";
        }
        if ($pressedKey !== '') {
            $lines[] = "  Pressed key: {$pressedKey}";
        }
        if ($attempts !== null && $attempts !== '' && (int) $attempts > 0) {
            $lines[] = "  Failed attempts: {$attempts}";
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
            return 'No calls found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} calls:\n";
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
