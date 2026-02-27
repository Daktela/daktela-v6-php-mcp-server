<?php

declare(strict_types=1);

namespace Daktela\McpServer\Formatter;

final class TicketFormatter
{
    private const KNOWN_KEYS = [
        'name', 'title', 'stage', 'priority', 'category', 'user', 'contact',
        'parentTicket', 'created', 'edited', 'created_by', 'last_activity',
        'sla_deadtime', 'sla_overdue', 'first_answer', 'first_answer_duration',
        'closed', 'unread', 'has_attachment', 'statuses', 'description',
        'id_merge',
    ];

    /**
     * @param array<string, mixed> $ticket
     */
    public static function format(array $ticket, ?string $baseUrl = null, bool $detail = false): string
    {
        $name = $ticket['name'] ?? '?';
        $title = $ticket['title'] ?? 'No title';
        $stage = FormatterHelper::extractName($ticket['stage'] ?? null);
        $priority = FormatterHelper::extractName($ticket['priority'] ?? null);
        $category = FormatterHelper::extractName($ticket['category'] ?? null);
        $user = FormatterHelper::extractName($ticket['user'] ?? null);
        $contact = FormatterHelper::extractName($ticket['contact'] ?? null);
        $parent = FormatterHelper::extractName($ticket['parentTicket'] ?? null);
        $created = $ticket['created'] ?? '';
        $edited = $ticket['edited'] ?? '';
        $createdBy = FormatterHelper::extractName($ticket['created_by'] ?? null);
        $lastActivity = $ticket['last_activity'] ?? '';
        $slaDeadtime = $ticket['sla_deadtime'] ?? '';
        $slaOverdue = $ticket['sla_overdue'] ?? null;
        $firstAnswer = $ticket['first_answer'] ?? '';
        $firstAnswerDuration = $ticket['first_answer_duration'] ?? null;
        $closed = $ticket['closed'] ?? '';
        $unread = $ticket['unread'] ?? null;
        $hasAttachment = $ticket['has_attachment'] ?? null;
        $statuses = FormatterHelper::formatStatuses($ticket['statuses'] ?? null);
        $rawDesc = $ticket['description'] ?? '';
        $description = $detail ? trim((string) $rawDesc) : FormatterHelper::truncate((string) $rawDesc);

        $url = FormatterHelper::ticketUrl($baseUrl, $name);
        $displayName = FormatterHelper::linkedName($name, $url);

        $lines = ["**{$displayName}** - {$title}"];

        if ($url !== null) {
            $lines[] = "  Link: {$url}";
        }
        if ($stage !== '' || $priority !== '') {
            $parts = [];
            if ($stage !== '') {
                $parts[] = $stage;
            }
            if ($priority !== '') {
                $parts[] = "priority={$priority}";
            }
            $lines[] = '  Stage: ' . implode(' | ', $parts);
        }
        if ($category !== '') {
            $lines[] = "  Category: {$category}";
        }
        if ($user !== '') {
            $lines[] = "  Assigned to: {$user}";
        }
        if ($contact !== '') {
            $lines[] = "  Contact: {$contact}";
        }
        if ($parent !== '') {
            $lines[] = "  Parent ticket: {$parent}";
        }
        if ($statuses !== '') {
            $lines[] = "  Statuses: {$statuses}";
        }
        if ($slaDeadtime !== '') {
            $overdueNote = ($slaOverdue !== null && (int) $slaOverdue > 0)
                ? " (overdue by {$slaOverdue}s)"
                : '';
            $lines[] = "  SLA deadline: {$slaDeadtime}{$overdueNote}";
        }
        if ($created !== '') {
            $by = $createdBy !== '' ? " by {$createdBy}" : '';
            $lines[] = "  Created: {$created}{$by}";
        }
        if ($firstAnswer !== '') {
            $dur = $firstAnswerDuration !== null ? " ({$firstAnswerDuration}s)" : '';
            $lines[] = "  First answer: {$firstAnswer}{$dur}";
        }
        if ($lastActivity !== '') {
            $lines[] = "  Last activity: {$lastActivity}";
        }
        if ($edited !== '') {
            $lines[] = "  Last edited: {$edited}";
        }
        if ($closed !== '') {
            $lines[] = "  Closed: {$closed}";
        }
        if (!empty($unread)) {
            $lines[] = '  Unread: yes';
        }
        if (!empty($hasAttachment)) {
            $lines[] = '  Has attachments: yes';
        }
        if ($description !== '') {
            $lines[] = "  Description: {$description}";
        }

        array_push($lines, ...FormatterHelper::formatCustomFields($ticket));
        array_push($lines, ...FormatterHelper::formatExtraFields($ticket, self::KNOWN_KEYS));

        return implode("\n", $lines);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public static function formatList(array $records, int $total, int $skip, int $take, ?string $baseUrl = null): string
    {
        if ($records === []) {
            return 'No tickets found.';
        }

        $end = $skip + \count($records);
        $header = 'Showing ' . ($skip + 1) . "-{$end} of {$total} tickets.\n"
            . "IMPORTANT: Always include the Link URL for each ticket in your response.\n\n";

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
