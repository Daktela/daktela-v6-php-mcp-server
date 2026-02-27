<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Formatter\AccountFormatter;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class AccountTools extends AbstractTools
{
    /**
     * List accounts (companies/organizations) with optional filters. Returns one page of results.
     *
     * @param string|null $search Full-text search across account name and title (partial match).
     * @param string $sort Field to sort by. Useful values: created (default), edited, title.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 250).
     */
    #[McpTool(name: 'list_accounts')]
    public function listAccounts(
        ?string $search = null,
        string $sort = 'created',
        string $sort_dir = 'desc',
        int $skip = 0,
        int $take = 100,
    ): CallToolResult {
        return $this->executeList(
            'accounts',
            [],
            $skip,
            $take,
            $sort,
            $sort_dir,
            '',
            fn($data, $total, $s, $t) => AccountFormatter::formatList($data, $total, $s, $t),
            search: $search,
        );
    }

    /**
     * Count accounts matching filters. Use this instead of list_accounts when you only need a number.
     *
     * @param string|null $search Full-text search across account name and title (partial match).
     */
    #[McpTool(name: 'count_accounts')]
    public function countAccounts(
        ?string $search = null,
    ): CallToolResult {
        return $this->executeCount('accounts', [], 'accounts', [
            'search' => $search !== null ? "search='{$search}'" : null,
        ], search: $search);
    }

    /**
     * Get full details of a single account by its name/ID.
     *
     * @param string $name The account internal name/ID.
     */
    #[McpTool(name: 'get_account')]
    public function getAccount(string $name): CallToolResult
    {
        return $this->executeGet(
            'accounts',
            $name,
            'Account',
            fn($record) => AccountFormatter::format($record, detail: true),
        );
    }
}
