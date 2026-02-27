<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Client\DaktelaApiException;
use Daktela\McpServer\Filter\DateFilterHelper;
use Daktela\McpServer\Filter\FilterHelper;
use Daktela\McpServer\Formatter\CampaignRecordFormatter;
use Daktela\McpServer\Formatter\SimpleRecordFormatter;
use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class CampaignTools extends AbstractTools
{
    /**
     * List campaign records with optional filters. Returns one page of results.
     *
     * @param string|null $type Filter by campaign record type internal name.
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $action Filter by campaign record action/status.
     * @param string|null $date_from Filter campaign records created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter campaign records created on or before this date (YYYY-MM-DD).
     * @param string $sort Field to sort by. Useful values: created (default), edited, nextcall.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 250).
     */
    #[McpTool(name: 'list_campaign_records')]
    public function listCampaignRecords(
        ?string $type = null,
        ?string $user = null,
        ?string $action = null,
        ?string $date_from = null,
        ?string $date_to = null,
        string $sort = 'created',
        string $sort_dir = 'desc',
        int $skip = 0,
        int $take = 100,
    ): CallToolResult {
        try {
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
            ['record_type', 'eq', $type],
            ['user', 'eq', $user],
            ['action', 'eq', $action],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('created', $date_from, $date_to));

        return $this->executeList(
            'campaignsRecords',
            $filters,
            $skip,
            $take,
            $sort,
            $sort_dir,
            $header,
            fn($data, $total, $s, $t) => CampaignRecordFormatter::formatList($data, $total, $s, $t),
        );
    }

    /**
     * Count campaign records matching filters. Use this instead of list_campaign_records when you only need a number.
     *
     * @param string|null $type Filter by campaign record type internal name.
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $action Filter by campaign record action/status.
     * @param string|null $date_from Filter campaign records created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter campaign records created on or before this date (YYYY-MM-DD).
     */
    #[McpTool(name: 'count_campaign_records')]
    public function countCampaignRecords(
        ?string $type = null,
        ?string $user = null,
        ?string $action = null,
        ?string $date_from = null,
        ?string $date_to = null,
    ): CallToolResult {
        try {
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        [$user, ] = $this->resolveUser($user);

        $filters = FilterHelper::fromNullable([
            ['record_type', 'eq', $type],
            ['user', 'eq', $user],
            ['action', 'eq', $action],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('created', $date_from, $date_to));

        return $this->executeCount('campaignsRecords', $filters, 'campaign records', [
            'type' => $type !== null ? "type={$type}" : null,
            'user' => $user !== null ? "user={$user}" : null,
            'action' => $action !== null ? "action={$action}" : null,
            'date_from' => $date_from !== null ? "from {$date_from}" : null,
            'date_to' => $date_to !== null ? "to {$date_to}" : null,
        ]);
    }

    /**
     * Get full details of a single campaign record by its name/ID.
     *
     * @param string $name The campaign record internal name/ID.
     */
    #[McpTool(name: 'get_campaign_record')]
    public function getCampaignRecord(string $name): CallToolResult
    {
        return $this->executeGet(
            'campaignsRecords',
            $name,
            'Campaign record',
            fn($record) => CampaignRecordFormatter::format($record),
        );
    }

    /**
     * List all campaign types. Returns available campaign type definitions.
     *
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 200).
     */
    #[McpTool(name: 'list_campaign_types')]
    public function listCampaignTypes(int $skip = 0, int $take = 200): CallToolResult
    {
        try {
            $result = $this->client->list('campaignsTypes', skip: $skip, take: $take);
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        return self::success(SimpleRecordFormatter::formatList(
            $result['data'],
            $result['total'],
            $skip,
            $take,
            'campaign types',
        ));
    }
}
