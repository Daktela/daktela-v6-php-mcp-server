<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class FormatterHelper
{
    public const MAX_DESCRIPTION_LENGTH = 300;

    /**
     * Extract a display name from a related object field.
     *
     * Daktela returns related objects as either:
     * - A string (the name/ID)
     * - A dict with 'title' or 'name' key
     * - null
     */
    public static function extractName(mixed $obj): string
    {
        if ($obj === null) {
            return '';
        }
        if (\is_string($obj)) {
            return $obj;
        }
        if (\is_array($obj)) {
            return (string) ($obj['title'] ?? $obj['name'] ?? '');
        }

        return (string) $obj;
    }

    /**
     * Extract the internal ID (name field) from a related object.
     *
     * Unlike extractName which prefers 'title' for display,
     * this returns the raw 'name' field used for API lookups and URLs.
     */
    public static function extractId(mixed $obj): string
    {
        if ($obj === null) {
            return '';
        }
        if (\is_string($obj) || \is_int($obj)) {
            return (string) $obj;
        }
        if (\is_array($obj)) {
            $name = $obj['name'] ?? null;

            return $name !== null ? (string) $name : '';
        }

        return '';
    }

    public static function truncate(?string $text, int $maxLen = self::MAX_DESCRIPTION_LENGTH): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        $text = trim($text);
        if (\strlen($text) <= $maxLen) {
            return $text;
        }

        return mb_substr($text, 0, $maxLen) . '...';
    }

    /**
     * Extract status labels from a statuses MN relation.
     */
    public static function formatStatuses(mixed $statuses): string
    {
        if (empty($statuses)) {
            return '';
        }
        if (\is_array($statuses) && array_is_list($statuses)) {
            $names = array_filter(array_map(self::extractName(...), $statuses));

            return implode(', ', $names);
        }

        return self::extractName($statuses);
    }

    /**
     * Convert a field key like 'lead_type' or 'leadType' to 'Lead type'.
     */
    public static function readableLabel(string $key): string
    {
        $label = (string) preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $key);
        $label = str_replace(['_', '-'], ' ', $label);
        $label = trim($label);

        return $label !== '' ? ucfirst($label) : $key;
    }

    /**
     * Format a single field value for display. Returns null if empty/unrenderable.
     */
    public static function formatValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (\is_array($value)) {
            if ($value === []) {
                return null;
            }
            if (!array_is_list($value)) {
                $name = $value['title'] ?? $value['name'] ?? null;

                return $name !== null ? (string) $name : null;
            }
            $items = [];
            foreach ($value as $v) {
                if (\is_array($v)) {
                    $n = self::extractName($v);
                    if ($n !== '') {
                        $items[] = $n;
                    }
                } elseif ($v !== null && $v !== '') {
                    $items[] = (string) $v;
                }
            }

            return $items !== [] ? implode(', ', $items) : null;
        }
        if (\is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    /**
     * Render custom fields from a record's customFields dict.
     *
     * @param array<string, mixed> $record
     * @return list<string>
     */
    public static function formatCustomFields(array $record): array
    {
        $custom = $record['customFields'] ?? null;
        if (!\is_array($custom) || $custom === []) {
            return [];
        }

        $lines = [];
        foreach ($custom as $key => $value) {
            $display = self::formatValue($value);
            if ($display === null) {
                continue;
            }
            $lines[] = '  ' . self::readableLabel($key) . ': ' . $display;
        }

        return $lines;
    }

    /**
     * Render top-level fields not in the known set.
     *
     * @param array<string, mixed> $record
     * @param list<string> $knownKeys
     * @return list<string>
     */
    public static function formatExtraFields(array $record, array $knownKeys): array
    {
        $known = array_flip($knownKeys);
        $lines = [];

        foreach ($record as $key => $value) {
            if (isset($known[$key]) || $key === 'customFields') {
                continue;
            }
            if (str_starts_with($key, '_')) {
                continue;
            }
            $display = self::formatValue($value);
            if ($display === null) {
                continue;
            }
            $lines[] = '  ' . self::readableLabel($key) . ': ' . $display;
        }

        return $lines;
    }

    /**
     * Build a Daktela web UI URL for a ticket.
     */
    public static function ticketUrl(?string $baseUrl, mixed $ticketName): ?string
    {
        if ($baseUrl === null || $baseUrl === '' || empty($ticketName)) {
            return null;
        }

        return rtrim($baseUrl, '/') . '/tickets/update/' . $ticketName;
    }

    /**
     * Extract the ticket numeric ID from the activities list on an email/chat record.
     */
    public static function extractTicketFromActivities(mixed $activities): ?string
    {
        if (!\is_array($activities) || $activities === []) {
            return null;
        }
        foreach ($activities as $act) {
            if (!\is_array($act)) {
                continue;
            }
            $ticket = $act['ticket'] ?? null;
            if ($ticket === null) {
                continue;
            }
            $id = self::extractId($ticket);
            if ($id !== '') {
                return $id;
            }
        }

        return null;
    }

    /**
     * Wrap a record name in a markdown link if URL is available.
     */
    public static function linkedName(mixed $name, ?string $url): string
    {
        $name = (string) $name;
        if ($url !== null && $url !== '') {
            return "[{$name}]({$url})";
        }

        return $name;
    }
}
