<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Enum\Direction;
use Daktela\McpServer\Filter\DateFilterHelper;
use Daktela\McpServer\Filter\FilterHelper;
use Daktela\McpServer\Formatter\EmailFormatter;
use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class EmailTools extends AbstractTools
{
    private const EMAIL_LIST_FIELDS = [
        'name', 'queue', 'user', 'title', 'address', 'direction',
        'wait_time', 'duration', 'answered', 'text', 'time', 'state',
    ];

    /**
     * List emails with optional filters. Returns one page of results.
     *
     * @param string|null $queue Filter by queue internal name (use list_queues to find valid names).
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID.
     * @param string|null $direction Filter by email direction: 'in' or 'out' (lowercase).
     * @param string|null $date_from Filter emails on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter emails on or before this date (YYYY-MM-DD).
     * @param string $sort Field to sort by. Useful values: time (default), duration, wait_time.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 250).
     */
    #[McpTool(name: 'list_emails')]
    public function listEmails(
        ?string $queue = null,
        ?string $user = null,
        ?string $contact = null,
        #[CompletionProvider(enum: Direction::class)]
        ?string $direction = null,
        ?string $date_from = null,
        ?string $date_to = null,
        string $sort = 'time',
        string $sort_dir = 'desc',
        int $skip = 0,
        int $take = 100,
    ): CallToolResult {
        try {
            $direction = InputValidator::direction($direction);
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
            ['queue', 'eq', $queue],
            ['user', 'eq', $user],
            ['contact', 'eq', $contact],
            ['direction', 'eq', $direction],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('time', $date_from, $date_to));

        return $this->executeList(
            'activitiesEmail',
            $filters,
            $skip,
            $take,
            $sort,
            $sort_dir,
            $header,
            fn($data, $total, $s, $t) => EmailFormatter::formatList($data, $total, $s, $t, $this->client->getBaseUrl()),
            fields: self::EMAIL_LIST_FIELDS,
        );
    }

    /**
     * Count emails matching filters. Use this instead of list_emails when you only need a number.
     *
     * @param string|null $queue Filter by queue internal name (use list_queues to find valid names).
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID.
     * @param string|null $direction Filter by email direction: 'in' or 'out' (lowercase).
     * @param string|null $date_from Filter emails on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter emails on or before this date (YYYY-MM-DD).
     */
    #[McpTool(name: 'count_emails')]
    public function countEmails(
        ?string $queue = null,
        ?string $user = null,
        ?string $contact = null,
        #[CompletionProvider(enum: Direction::class)]
        ?string $direction = null,
        ?string $date_from = null,
        ?string $date_to = null,
    ): CallToolResult {
        try {
            $direction = InputValidator::direction($direction);
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        [$user, ] = $this->resolveUser($user);

        $filters = FilterHelper::fromNullable([
            ['queue', 'eq', $queue],
            ['user', 'eq', $user],
            ['contact', 'eq', $contact],
            ['direction', 'eq', $direction],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('time', $date_from, $date_to));

        return $this->executeCount('activitiesEmail', $filters, 'emails', [
            'queue' => $queue !== null ? "queue={$queue}" : null,
            'user' => $user !== null ? "user={$user}" : null,
            'contact' => $contact !== null ? "contact={$contact}" : null,
            'direction' => $direction !== null ? "direction={$direction}" : null,
            'date_from' => $date_from !== null ? "from {$date_from}" : null,
            'date_to' => $date_to !== null ? "to {$date_to}" : null,
        ]);
    }

    /**
     * Get full details of a single email by its name/ID.
     *
     * @param string $name The email internal name/ID.
     */
    #[McpTool(name: 'get_email')]
    public function getEmail(string $name): CallToolResult
    {
        return $this->executeGet(
            'activitiesEmail',
            $name,
            'Email',
            fn($record) => EmailFormatter::format($record, baseUrl: $this->client->getBaseUrl()),
        );
    }
}
