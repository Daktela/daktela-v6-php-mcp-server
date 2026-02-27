<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class TranscriptFormatter
{
    /**
     * Format transcript segments into chronological dialogue.
     *
     * @param list<array<string, mixed>> $segments
     */
    public static function format(array $segments, ?string $activityName = null): string
    {
        if ($segments === []) {
            return 'No transcript segments found.';
        }

        // Sort by start time ascending
        usort($segments, fn(array $a, array $b) => ((float) ($a['start'] ?? 0)) <=> ((float) ($b['start'] ?? 0)));

        $header = '**Transcript**';
        if ($activityName !== null && $activityName !== '') {
            $header .= " ({$activityName})";
        }

        $lines = [$header];
        foreach ($segments as $seg) {
            $start = $seg['start'] ?? 0;
            $text = trim((string) ($seg['text'] ?? ''));
            $type = $seg['type'] ?? '';

            // Format timestamp as M:SS
            $totalSeconds = (int) $start;
            $minutes = intdiv($totalSeconds, 60);
            $seconds = $totalSeconds % 60;
            $timestamp = sprintf('%d:%02d', $minutes, $seconds);

            // Determine speaker from type field
            $speaker = match ($type) {
                'customer', 'Customer' => 'Customer',
                default => 'Operator',
            };

            $lines[] = "  [{$timestamp}] {$speaker}: {$text}";
        }

        return implode("\n", $lines);
    }
}
