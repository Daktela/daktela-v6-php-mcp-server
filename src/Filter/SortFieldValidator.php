<?php

declare(strict_types=1);

namespace Daktela\McpServer\Filter;

final class SortFieldValidator
{
    /** @var array<string, list<string>> */
    private const SORT_FIELDS = [
        'tickets' => [
            'name', 'title', 'created', 'edited', 'last_activity',
            'last_activity_operator', 'last_activity_client',
            'sla_deadtime', 'sla_close_deadline', 'priority', 'stage',
            'first_answer', 'closed',
        ],
        'activities' => ['time', 'time_close', 'duration', 'ringing_time'],
        'activitiesCall' => ['call_time', 'duration', 'waiting_time', 'ringing_time'],
        'activitiesEmail' => ['time', 'duration', 'wait_time'],
        'activitiesWeb' => ['time', 'duration', 'wait_time'],
        'activitiesSms' => ['time', 'duration', 'wait_time'],
        'activitiesFbm' => ['time', 'duration', 'wait_time'],
        'activitiesIgdm' => ['time', 'duration', 'wait_time'],
        'activitiesWap' => ['time', 'duration', 'wait_time'],
        'activitiesVbr' => ['time', 'duration', 'wait_time'],
        'contacts' => ['created', 'edited', 'title', 'lastname'],
        'accounts' => ['created', 'edited', 'title'],
        'crmRecords' => ['created', 'edited', 'title', 'stage'],
        'campaignsRecords' => ['created', 'edited', 'nextcall'],
        'activitiesCallTranscripts' => ['start', 'end'],
    ];

    public static function validate(string $endpoint, ?string $sort): ?string
    {
        if ($sort === null) {
            return null;
        }

        $allowed = self::SORT_FIELDS[$endpoint] ?? null;
        if ($allowed === null) {
            return $sort; // unknown endpoint — pass through
        }

        return \in_array($sort, $allowed, true) ? $sort : null;
    }
}
