<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class RealtimeSessionFormatter
{
    private const KNOWN_KEYS = [
        'id_agent', 'state', 'exten', 'exten_status', 'logintime',
        'lastcalltime', 'statetime', 'id_pause', 'onpause',
    ];

    /**
     * @param array<string, mixed> $record
     */
    public static function format(array $record): string
    {
        $agent = FormatterHelper::extractName($record['id_agent'] ?? null);
        $state = $record['state'] ?? '';
        $exten = $record['exten'] ?? '';
        $extenStatus = $record['exten_status'] ?? '';
        $loginTime = $record['logintime'] ?? '';
        $lastCallTime = $record['lastcalltime'] ?? '';
        $stateTime = $record['statetime'] ?? '';
        $pauseType = FormatterHelper::extractName($record['id_pause'] ?? null);
        $onPause = $record['onpause'] ?? '';

        $header = $agent !== '' ? "**{$agent}**" : '**Unknown agent**';
        $lines = [$header];

        if ($state !== '') {
            $lines[] = "  State: {$state}";
        }
        if ($pauseType !== '') {
            $lines[] = "  Pause type: {$pauseType}";
        }
        if ($onPause !== '') {
            $lines[] = "  Pause since: {$onPause}";
        }
        if ($exten !== '') {
            $extenDisplay = $exten;
            if ($extenStatus !== '') {
                $extenDisplay .= " ({$extenStatus})";
            }
            $lines[] = "  Extension: {$extenDisplay}";
        }
        if ($loginTime !== '') {
            $lines[] = "  Login time: {$loginTime}";
        }
        if ($lastCallTime !== '') {
            $lines[] = "  Last call: {$lastCallTime}";
        }
        if ($stateTime !== '') {
            $lines[] = "  In state since: {$stateTime}";
        }

        array_push($lines, ...FormatterHelper::formatExtraFields($record, self::KNOWN_KEYS));

        return implode("\n", $lines);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public static function formatList(array $records, int $total, int $skip, int $take): string
    {
        if ($records === []) {
            return 'No realtime sessions found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} realtime sessions:\n";
        $body = implode("\n\n", array_map(self::format(...), $records));
        $footer = '';
        if ($end < $total) {
            $footer = "\n\n(Use skip={$end} to see next page)";
        }

        return $header . $body . $footer;
    }
}
