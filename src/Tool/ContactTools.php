<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Filter\DateFilterHelper;
use Daktela\McpServer\Filter\FilterHelper;
use Daktela\McpServer\Formatter\ContactFormatter;
use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class ContactTools extends AbstractTools
{
    /**
     * List contacts with optional filters. Returns one page of results.
     *
     * @param string|null $search Full-text search across contact name, email, phone (partial match).
     * @param string|null $user Owner/agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $account Filter by account internal ID (use list_accounts to find valid IDs).
     * @param string|null $date_from Filter contacts created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter contacts created on or before this date (YYYY-MM-DD).
     * @param string $sort Field to sort by. Useful values: created (default), edited, title, lastname.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 250).
     */
    #[McpTool(name: 'list_contacts')]
    public function listContacts(
        ?string $search = null,
        ?string $user = null,
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
            ['user', 'eq', $user],
            ['account', 'eq', $account],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('created', $date_from, $date_to));

        return $this->executeList(
            'contacts',
            $filters,
            $skip,
            $take,
            $sort,
            $sort_dir,
            $header,
            fn($data, $total, $s, $t) => ContactFormatter::formatList($data, $total, $s, $t),
            search: $search,
        );
    }

    /**
     * Count contacts matching filters. Use this instead of list_contacts when you only need a number.
     *
     * @param string|null $search Full-text search across contact name, email, phone (partial match).
     * @param string|null $user Owner/agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $account Filter by account internal ID (use list_accounts to find valid IDs).
     * @param string|null $date_from Filter contacts created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter contacts created on or before this date (YYYY-MM-DD).
     */
    #[McpTool(name: 'count_contacts')]
    public function countContacts(
        ?string $search = null,
        ?string $user = null,
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
            ['user', 'eq', $user],
            ['account', 'eq', $account],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('created', $date_from, $date_to));

        return $this->executeCount('contacts', $filters, 'contacts', [
            'search' => $search !== null ? "search='{$search}'" : null,
            'user' => $user !== null ? "user={$user}" : null,
            'account' => $account !== null ? "account={$account}" : null,
            'date_from' => $date_from !== null ? "from {$date_from}" : null,
            'date_to' => $date_to !== null ? "to {$date_to}" : null,
        ], search: $search);
    }

    /**
     * Get full details of a single contact by its name/ID.
     *
     * @param string $name The contact internal name/ID (e.g. 'contact_674eda46162a8403430453').
     */
    #[McpTool(name: 'get_contact')]
    public function getContact(string $name): CallToolResult
    {
        return $this->executeGet(
            'contacts',
            $name,
            'Contact',
            fn($record) => ContactFormatter::format($record),
        );
    }
}
