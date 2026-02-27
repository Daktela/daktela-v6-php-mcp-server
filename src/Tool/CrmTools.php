<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Filter\DateFilterHelper;
use Daktela\McpServer\Filter\FilterHelper;
use Daktela\McpServer\Formatter\CrmRecordFormatter;
use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class CrmTools extends AbstractTools
{
    /**
     * List CRM records with optional filters. Returns one page of results.
     *
     * @param string|null $type Filter by CRM record type internal name.
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID.
     * @param string|null $account Filter by account internal ID.
     * @param string|null $date_from Filter CRM records created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter CRM records created on or before this date (YYYY-MM-DD).
     * @param string $sort Field to sort by. Useful values: created (default), edited, title, stage.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 250).
     */
    #[McpTool(name: 'list_crm_records')]
    public function listCrmRecords(
        ?string $type = null,
        ?string $user = null,
        ?string $contact = null,
        ?string $account = null,
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
            ['type', 'eq', $type],
            ['user', 'eq', $user],
            ['contact', 'eq', $contact],
            ['account', 'eq', $account],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('created', $date_from, $date_to));

        return $this->executeList(
            'crmRecords',
            $filters,
            $skip,
            $take,
            $sort,
            $sort_dir,
            $header,
            fn($data, $total, $s, $t) => CrmRecordFormatter::formatList($data, $total, $s, $t, $this->client->getBaseUrl()),
        );
    }

    /**
     * Count CRM records matching filters. Use this instead of list_crm_records when you only need a number.
     *
     * @param string|null $type Filter by CRM record type internal name.
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID.
     * @param string|null $account Filter by account internal ID.
     * @param string|null $date_from Filter CRM records created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter CRM records created on or before this date (YYYY-MM-DD).
     */
    #[McpTool(name: 'count_crm_records')]
    public function countCrmRecords(
        ?string $type = null,
        ?string $user = null,
        ?string $contact = null,
        ?string $account = null,
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
            ['type', 'eq', $type],
            ['user', 'eq', $user],
            ['contact', 'eq', $contact],
            ['account', 'eq', $account],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('created', $date_from, $date_to));

        return $this->executeCount('crmRecords', $filters, 'CRM records', [
            'type' => $type !== null ? "type={$type}" : null,
            'user' => $user !== null ? "user={$user}" : null,
            'contact' => $contact !== null ? "contact={$contact}" : null,
            'account' => $account !== null ? "account={$account}" : null,
            'date_from' => $date_from !== null ? "from {$date_from}" : null,
            'date_to' => $date_to !== null ? "to {$date_to}" : null,
        ]);
    }

    /**
     * Get full details of a single CRM record by its name/ID.
     *
     * @param string $name The CRM record internal name/ID.
     */
    #[McpTool(name: 'get_crm_record')]
    public function getCrmRecord(string $name): CallToolResult
    {
        return $this->executeGet(
            'crmRecords',
            $name,
            'CRM record',
            fn($record) => CrmRecordFormatter::format($record, baseUrl: $this->client->getBaseUrl(), detail: true),
        );
    }
}
