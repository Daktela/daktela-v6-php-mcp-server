<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Enum\ActivityAction;
use Daktela\McpServer\Enum\ActivityType;
use Daktela\McpServer\Filter\DateFilterHelper;
use Daktela\McpServer\Filter\FilterHelper;
use Daktela\McpServer\Formatter\ActivityFormatter;
use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class ActivityTools extends AbstractTools
{
    /**
     * List activities with optional filters. Returns one page of results.
     *
     * @param string|null $type Activity type filter (e.g. 'CALL', 'EMAIL', 'CHAT', 'SMS', 'FB_MESSENGER', 'INSTAGRAM', 'WHATSAPP', 'VIBER').
     * @param string|null $action Activity action/status filter.
     * @param string|null $queue Filter by queue internal name (use list_queues to find valid names).
     * @param string|null $ticket Filter by ticket ID (numeric, e.g. '787979').
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $date_from Filter activities on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter activities on or before this date (YYYY-MM-DD).
     * @param string $sort Field to sort by. Useful values: time (default), time_close, duration, ringing_time.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 250).
     */
    #[McpTool(name: 'list_activities')]
    public function listActivities(
        #[CompletionProvider(enum: ActivityType::class)]
        ?string $type = null,
        #[CompletionProvider(enum: ActivityAction::class)]
        ?string $action = null,
        ?string $queue = null,
        ?string $ticket = null,
        ?string $user = null,
        ?string $date_from = null,
        ?string $date_to = null,
        string $sort = 'time',
        string $sort_dir = 'desc',
        int $skip = 0,
        int $take = 100,
    ): CallToolResult {
        try {
            $type = InputValidator::activityType($type);
            $action = InputValidator::activityAction($action);
            $sort_dir = InputValidator::sortDirection($sort_dir);
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
            $skip = InputValidator::skip($skip);
            $take = InputValidator::take($take);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        [$user, $header] = $this->resolveUser($user);

        $filters = FilterHelper::fromNullable([
            ['type', 'eq', $type],
            ['action', 'eq', $action],
            ['queue', 'eq', $queue],
            ['ticket', 'eq', $ticket],
            ['user', 'eq', $user],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('time', $date_from, $date_to));

        return $this->executeList(
            'activities',
            $filters,
            $skip,
            $take,
            $sort,
            $sort_dir,
            $header,
            fn($data, $total, $s, $t) => ActivityFormatter::formatList($data, $total, $s, $t, $this->client->getBaseUrl()),
        );
    }

    /**
     * Count activities matching filters. Use this instead of list_activities when you only need a number.
     *
     * @param string|null $type Activity type filter (e.g. 'CALL', 'EMAIL', 'CHAT', 'SMS', 'FB_MESSENGER', 'INSTAGRAM', 'WHATSAPP', 'VIBER').
     * @param string|null $action Activity action/status filter.
     * @param string|null $queue Filter by queue internal name (use list_queues to find valid names).
     * @param string|null $ticket Filter by ticket ID (numeric, e.g. '787979').
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $date_from Filter activities on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter activities on or before this date (YYYY-MM-DD).
     */
    #[McpTool(name: 'count_activities')]
    public function countActivities(
        #[CompletionProvider(enum: ActivityType::class)]
        ?string $type = null,
        #[CompletionProvider(enum: ActivityAction::class)]
        ?string $action = null,
        ?string $queue = null,
        ?string $ticket = null,
        ?string $user = null,
        ?string $date_from = null,
        ?string $date_to = null,
    ): CallToolResult {
        try {
            $type = InputValidator::activityType($type);
            $action = InputValidator::activityAction($action);
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        [$user, ] = $this->resolveUser($user);

        $filters = FilterHelper::fromNullable([
            ['type', 'eq', $type],
            ['action', 'eq', $action],
            ['queue', 'eq', $queue],
            ['ticket', 'eq', $ticket],
            ['user', 'eq', $user],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('time', $date_from, $date_to));

        return $this->executeCount('activities', $filters, 'activities', [
            'type' => $type !== null ? "type={$type}" : null,
            'action' => $action !== null ? "action={$action}" : null,
            'queue' => $queue !== null ? "queue={$queue}" : null,
            'ticket' => $ticket !== null ? "ticket={$ticket}" : null,
            'user' => $user !== null ? "user={$user}" : null,
            'date_from' => $date_from !== null ? "from {$date_from}" : null,
            'date_to' => $date_to !== null ? "to {$date_to}" : null,
        ]);
    }

    /**
     * Get full details of a single activity by its name/ID.
     *
     * @param string $name The activity internal name/ID.
     */
    #[McpTool(name: 'get_activity')]
    public function getActivity(string $name): CallToolResult
    {
        return $this->executeGet(
            'activities',
            $name,
            'Activity',
            fn($record) => ActivityFormatter::format($record, baseUrl: $this->client->getBaseUrl(), detail: true),
        );
    }
}
