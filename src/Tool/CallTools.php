<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Client\DaktelaApiException;
use Daktela\McpServer\Enum\Direction;
use Daktela\McpServer\Filter\DateFilterHelper;
use Daktela\McpServer\Filter\FilterHelper;
use Daktela\McpServer\Formatter\CallFormatter;
use Daktela\McpServer\Formatter\TranscriptFormatter;
use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class CallTools extends AbstractTools
{
    private const CALL_LIST_FIELDS = [
        'id_call', 'call_time', 'direction', 'answered', 'id_queue', 'id_agent',
        'clid', 'prefix_clid_name', 'did', 'waiting_time', 'ringing_time',
        'hold_time', 'duration', 'disposition_cause', 'disconnection_cause',
        'pressed_key', 'missed_call', 'missed_call_time', 'missed_callback',
        'attempts',
    ];

    /**
     * List calls with optional filters. Returns one page of results.
     *
     * @param string|null $queue Filter by queue internal name (use list_queues to find valid names).
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID.
     * @param string|null $direction Filter by call direction: 'IN' or 'OUT'.
     * @param bool|null $answered Filter by whether the call was answered (true/false).
     * @param string|null $date_from Filter calls on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter calls on or before this date (YYYY-MM-DD).
     * @param string $sort Field to sort by. Useful values: call_time (default), duration, waiting_time, ringing_time.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 250).
     */
    #[McpTool(name: 'list_calls')]
    public function listCalls(
        ?string $queue = null,
        ?string $user = null,
        ?string $contact = null,
        #[CompletionProvider(enum: Direction::class)]
        ?string $direction = null,
        ?bool $answered = null,
        ?string $date_from = null,
        ?string $date_to = null,
        string $sort = 'call_time',
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
            ['id_queue', 'eq', $queue],
            ['id_agent', 'eq', $user],
            ['contact', 'eq', $contact],
            ['direction', 'eq', $direction],
            ['answered', 'eq', $answered !== null ? ($answered ? '1' : '0') : null],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('call_time', $date_from, $date_to));

        return $this->executeList(
            'activitiesCall',
            $filters,
            $skip,
            $take,
            $sort,
            $sort_dir,
            $header,
            fn($data, $total, $s, $t) => CallFormatter::formatList($data, $total, $s, $t, $this->client->getBaseUrl()),
            fields: self::CALL_LIST_FIELDS,
        );
    }

    /**
     * Count calls matching filters. Use this instead of list_calls when you only need a number.
     *
     * @param string|null $queue Filter by queue internal name (use list_queues to find valid names).
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID.
     * @param string|null $direction Filter by call direction: 'IN' or 'OUT'.
     * @param bool|null $answered Filter by whether the call was answered (true/false).
     * @param string|null $date_from Filter calls on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter calls on or before this date (YYYY-MM-DD).
     */
    #[McpTool(name: 'count_calls')]
    public function countCalls(
        ?string $queue = null,
        ?string $user = null,
        ?string $contact = null,
        #[CompletionProvider(enum: Direction::class)]
        ?string $direction = null,
        ?bool $answered = null,
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
            ['id_queue', 'eq', $queue],
            ['id_agent', 'eq', $user],
            ['contact', 'eq', $contact],
            ['direction', 'eq', $direction],
            ['answered', 'eq', $answered !== null ? ($answered ? '1' : '0') : null],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('call_time', $date_from, $date_to));

        return $this->executeCount('activitiesCall', $filters, 'calls', [
            'queue' => $queue !== null ? "queue={$queue}" : null,
            'user' => $user !== null ? "user={$user}" : null,
            'contact' => $contact !== null ? "contact={$contact}" : null,
            'direction' => $direction !== null ? "direction={$direction}" : null,
            'answered' => $answered !== null ? 'answered=' . ($answered ? 'yes' : 'no') : null,
            'date_from' => $date_from !== null ? "from {$date_from}" : null,
            'date_to' => $date_to !== null ? "to {$date_to}" : null,
        ]);
    }

    /**
     * Get full details of a single call by its name/ID.
     *
     * @param string $name The call internal name/ID.
     */
    #[McpTool(name: 'get_call')]
    public function getCall(string $name): CallToolResult
    {
        return $this->executeGet(
            'activitiesCall',
            $name,
            'Call',
            fn($record) => CallFormatter::format($record, baseUrl: $this->client->getBaseUrl()),
        );
    }

    /**
     * Get the transcript of a call. Returns text segments with speaker type and timestamps.
     *
     * @param string $activity The activity name/ID linked to the call (e.g. 'activities_67890abc').
     */
    #[McpTool(name: 'get_call_transcript')]
    public function getCallTranscript(string $activity): CallToolResult
    {
        try {
            $result = $this->client->list(
                'activitiesCallTranscripts',
                fieldFilters: [['activity', 'eq', $activity]],
                skip: 0,
                take: 200,
                sort: 'start',
                sortDir: 'asc',
            );
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        if ($result['data'] === []) {
            return self::success("No transcript found for activity '{$activity}'.");
        }

        return self::success(TranscriptFormatter::format($result['data']));
    }

    /**
     * List answered calls with their inline transcripts. Combines call metadata with transcript text.
     * Use this to search through call transcripts in bulk.
     *
     * @param string|null $date_from Filter calls on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter calls on or before this date (YYYY-MM-DD).
     * @param string|null $user Agent name — pass either a display name or login name. Resolved automatically.
     * @param string|null $queue Filter by queue internal name.
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of calls to return (default: 10, max: 50).
     */
    #[McpTool(name: 'list_call_transcripts')]
    public function listCallTranscripts(
        ?string $date_from = null,
        ?string $date_to = null,
        ?string $user = null,
        ?string $queue = null,
        int $skip = 0,
        int $take = 10,
    ): CallToolResult {
        try {
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
            $skip = InputValidator::skip($skip);
            $take = InputValidator::take($take, 50);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        [$user, $header] = $this->resolveUser($user);

        $filters = array_merge(
            [['answered', 'eq', '1']],
            FilterHelper::fromNullable([
                ['id_agent', 'eq', $user],
                ['id_queue', 'eq', $queue],
            ]),
            DateFilterHelper::build('call_time', $date_from, $date_to),
        );

        try {
            $callsResult = $this->client->list(
                'activitiesCall',
                fieldFilters: $filters,
                skip: $skip,
                take: $take,
                sort: 'call_time',
                sortDir: 'desc',
            );
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        if ($callsResult['data'] === []) {
            return self::success('No answered calls found.');
        }

        $parts = [];

        $total = $callsResult['total'];
        $end = $skip + \count($callsResult['data']);
        $parts[] = $header . 'Showing ' . ($skip + 1) . "-{$end} of {$total} answered calls with transcripts:";

        foreach ($callsResult['data'] as $call) {
            $callFormatted = CallFormatter::format($call, baseUrl: $this->client->getBaseUrl());

            // Extract activity name to fetch transcript
            $activities = $call['activities'] ?? [];
            $activityName = null;
            if (\is_array($activities)) {
                foreach ($activities as $act) {
                    if (\is_array($act)) {
                        $activityName = $act['name'] ?? null;
                        break;
                    }
                    if (\is_string($act)) {
                        $activityName = $act;
                        break;
                    }
                }
            }

            $transcriptText = '';
            if ($activityName !== null) {
                try {
                    $transcriptResult = $this->client->list(
                        'activitiesCallTranscripts',
                        fieldFilters: [['activity', 'eq', $activityName]],
                        skip: 0,
                        take: 200,
                        sort: 'start',
                        sortDir: 'asc',
                        fields: ['text', 'type', 'start', 'end'],
                    );

                    if ($transcriptResult['data'] !== []) {
                        $transcriptText = TranscriptFormatter::format($transcriptResult['data']);
                    }
                } catch (DaktelaApiException) {
                    $transcriptText = '(Error loading transcript)';
                }
            }

            if ($transcriptText !== '') {
                $parts[] = $callFormatted . "\n  --- Transcript ---\n" . $transcriptText;
            } else {
                $parts[] = $callFormatted . "\n  (No transcript available)";
            }
        }

        if ($end < $total) {
            $parts[] = "(Use skip={$end} to see next page)";
        }

        return self::success(implode("\n\n", $parts));
    }
}
