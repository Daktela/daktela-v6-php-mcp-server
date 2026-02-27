<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Client\DaktelaApiException;
use Daktela\McpServer\Enum\TicketPriority;
use Daktela\McpServer\Enum\TicketStage;
use Daktela\McpServer\Filter\DateFilterHelper;
use Daktela\McpServer\Filter\FilterHelper;
use Daktela\McpServer\Filter\SortFieldValidator;
use Daktela\McpServer\Formatter\ActivityFormatter;
use Daktela\McpServer\Formatter\SimpleRecordFormatter;
use Daktela\McpServer\Formatter\TicketFormatter;
use Daktela\McpServer\Resolver\UserResolver;
use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class TicketTools extends AbstractTools
{
    public const MAX_TAKE = 100;
    private const MAX_TAKE_DETAIL = 100;

    /** Fields requested for list endpoints — keeps API responses small. */
    private const TICKET_LIST_FIELDS = [
        'name', 'title', 'stage', 'priority', 'category', 'user', 'contact',
        'parentTicket', 'created', 'edited', 'created_by', 'last_activity',
        'sla_deadtime', 'sla_overdue', 'first_answer', 'first_answer_duration',
        'closed', 'unread', 'has_attachment', 'statuses', 'description',
        'id_merge', 'customFields',
    ];

    /**
     * List tickets with optional filters. Returns one page of results.
     *
     * @param string|null $category Filter by category internal name (use list_ticket_categories to find valid names).
     * @param string|null $stage Ticket lifecycle stage — exact values (case-sensitive): 'OPEN' = agent actively working on it, 'WAIT' = reply sent, awaiting customer response, 'CLOSE' = resolved/solved, 'ARCHIVE' = resolved and archived. When user says "open tickets", use stage='OPEN'.
     * @param string|null $priority Filter by priority: LOW, MEDIUM, HIGH.
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID (e.g. 'contact_674eda46162a8403430453'). NOT a person's name — call list_contacts(search='...') first to find the ID.
     * @param string|null $search Full-text search across ticket title and description (partial match).
     * @param string|null $status Filter by workflow status name (e.g. 'S0-Qualify', 'S1-Discovery'). Use list_statuses to see available status names.
     * @param string|null $date_from Filter tickets created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter tickets created on or before this date (YYYY-MM-DD).
     * @param bool $include_merged Include tickets that were merged into other tickets (default: false).
     * @param string $sort Field to sort by. Useful values: edited (default), created, sla_deadtime, sla_close_deadline, last_activity.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 100).
     */
    #[McpTool(name: 'list_tickets')]
    public function listTickets(
        ?string $category = null,
        #[CompletionProvider(enum: TicketStage::class)]
        ?string $stage = null,
        #[CompletionProvider(enum: TicketPriority::class)]
        ?string $priority = null,
        ?string $user = null,
        ?string $contact = null,
        ?string $search = null,
        ?string $status = null,
        ?string $date_from = null,
        ?string $date_to = null,
        bool $include_merged = false,
        string $sort = 'edited',
        string $sort_dir = 'desc',
        int $skip = 0,
        int $take = 100,
    ): CallToolResult {
        try {
            $stage = InputValidator::stage($stage);
            $priority = InputValidator::priority($priority);
            $sort_dir = InputValidator::sortDirection($sort_dir);
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
            $skip = InputValidator::skip($skip);
            $take = InputValidator::take($take, static::MAX_TAKE);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        [$user, $header] = $this->resolveUser($user);

        $filters = self::buildTicketFilters(
            category: $category,
            stage: $stage,
            priority: $priority,
            user: $user,
            contact: $contact,
            search: $search,
            status: $status,
            includeMerged: $include_merged,
            dateFrom: $date_from,
            dateTo: $date_to,
        );

        return $this->executeList(
            'tickets',
            $filters,
            $skip,
            $take,
            $sort,
            $sort_dir,
            $header,
            fn($data, $total, $s, $t) => TicketFormatter::formatList($data, $total, $s, $t, $this->client->getBaseUrl()),
            fields: self::TICKET_LIST_FIELDS,
        );
    }

    /**
     * Count tickets matching filters. Use this instead of list_tickets when you only need a number.
     *
     * @param string|null $category Filter by category internal name (use list_ticket_categories to find valid names).
     * @param string|null $stage Ticket lifecycle stage — exact values (case-sensitive): 'OPEN' = agent actively working, 'WAIT' = awaiting customer response, 'CLOSE' = resolved, 'ARCHIVE' = archived. When user says "open tickets", use stage='OPEN'.
     * @param string|null $priority Filter by priority: LOW, MEDIUM, HIGH.
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID. NOT a person's name — call list_contacts(search='...') first to find the ID.
     * @param string|null $search Full-text search across ticket title and description (partial match).
     * @param string|null $status Filter by workflow status name. Use list_statuses to see available status names.
     * @param string|null $date_from Filter tickets created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter tickets created on or before this date (YYYY-MM-DD).
     * @param bool $include_merged Include tickets that were merged into other tickets (default: false).
     */
    #[McpTool(name: 'count_tickets')]
    public function countTickets(
        ?string $category = null,
        #[CompletionProvider(enum: TicketStage::class)]
        ?string $stage = null,
        #[CompletionProvider(enum: TicketPriority::class)]
        ?string $priority = null,
        ?string $user = null,
        ?string $contact = null,
        ?string $search = null,
        ?string $status = null,
        ?string $date_from = null,
        ?string $date_to = null,
        bool $include_merged = false,
    ): CallToolResult {
        try {
            $stage = InputValidator::stage($stage);
            $priority = InputValidator::priority($priority);
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        $resolvedName = null;
        if ($user !== null) {
            [$user, $resolvedName] = UserResolver::resolve($this->client, $user);
        }

        $filters = self::buildTicketFilters(
            category: $category,
            stage: $stage,
            priority: $priority,
            user: $user,
            contact: $contact,
            search: $search,
            status: $status,
            includeMerged: $include_merged,
            dateFrom: $date_from,
            dateTo: $date_to,
        );

        $agentLabel = $resolvedName !== null ? "{$resolvedName} ({$user})" : $user;

        return $this->executeCount('tickets', $filters, 'tickets', [
            'category' => $category !== null ? "category={$category}" : null,
            'stage' => $stage !== null ? "stage={$stage}" : null,
            'priority' => $priority !== null ? "priority={$priority}" : null,
            'user' => $user !== null ? "user={$agentLabel}" : null,
            'contact' => $contact !== null ? "contact={$contact}" : null,
            'search' => $search !== null ? "search='{$search}'" : null,
            'status' => $status !== null ? "status={$status}" : null,
            'date_from' => $date_from !== null ? "from {$date_from}" : null,
            'date_to' => $date_to !== null ? "to {$date_to}" : null,
        ]);
    }

    /**
     * Get full details of a single ticket by its ID. Use this when you already know the ticket ID.
     *
     * @param string $name The ticket ID (numeric, e.g. 787979). If passed with a prefix like TK00787979, the prefix is stripped automatically.
     */
    #[McpTool(name: 'get_ticket')]
    public function getTicket(string $name): CallToolResult
    {
        $cleaned = ltrim($name, 'TKtk');
        $cleaned = ltrim($cleaned, '0') ?: $name;

        return $this->executeGet(
            'tickets',
            $cleaned,
            'Ticket',
            fn($record) => TicketFormatter::format($record, baseUrl: $this->client->getBaseUrl(), detail: true),
        );
    }

    /**
     * Get a ticket with all its activities and their content in one call.
     *
     * This is the recommended tool for analyzing a specific ticket — it returns the
     * ticket details plus all linked activities (calls, emails, chats, etc.) with
     * their descriptions and metadata, avoiding multiple round-trips.
     *
     * @param string $name The ticket ID (numeric, e.g. 787979). Prefix like TK00787979 is stripped automatically.
     * @param int $take Max number of activities to include (default: 100, max: 100).
     */
    #[McpTool(name: 'get_ticket_detail')]
    public function getTicketDetail(string $name, int $take = 100): CallToolResult
    {
        $cleaned = ltrim($name, 'TKtk');
        $cleaned = ltrim($cleaned, '0') ?: $name;
        $take = min($take, self::MAX_TAKE_DETAIL);
        $baseUrl = $this->client->getBaseUrl();

        try {
            $ticket = $this->client->get('tickets', $cleaned);
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }
        if ($ticket === null) {
            return self::success("Ticket '{$name}' not found.");
        }

        try {
            $activitiesResult = $this->client->list(
                'activities',
                fieldFilters: [['ticket', 'eq', $cleaned]],
                skip: 0,
                take: $take,
                sort: 'time',
                sortDir: 'asc',
            );
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        $parts = [TicketFormatter::format($ticket, baseUrl: $baseUrl, detail: true)];

        $activities = $activitiesResult['data'];
        $totalActivities = $activitiesResult['total'];

        if ($activities !== []) {
            $parts[] = "\n--- Activities (" . \count($activities) . " of {$totalActivities}) ---";
            foreach ($activities as $act) {
                $parts[] = ActivityFormatter::format($act, baseUrl: $baseUrl, detail: true);
            }
        } else {
            $parts[] = "\n--- No activities ---";
        }

        if ($totalActivities > $take) {
            $parts[] = "\n(Showing first {$take} of {$totalActivities} activities. "
                . "Use list_activities(ticket='{$cleaned}', skip={$take}) for more.)";
        }

        return self::success(implode("\n\n", $parts));
    }

    /**
     * List tickets for a specific account (company/organization).
     *
     * Accepts both a company name (e.g. 'Notino') or an internal account ID.
     * The tool resolves the name automatically. You do NOT need to call list_accounts first.
     *
     * @param string $account Company name (partial match, e.g. 'Notino', 'Siemens') or account ID.
     * @param string $stage Ticket stage filter (default: 'OPEN'). Values: 'OPEN', 'WAIT', 'CLOSE', 'ARCHIVE', 'ALL' = return tickets in any stage.
     * @param string|null $priority Filter by priority: LOW, MEDIUM, HIGH.
     * @param string|null $user Agent name — pass either a display name or login name. Resolved automatically.
     * @param string|null $category Filter by category internal name (use list_ticket_categories to find valid names).
     * @param string|null $date_from Filter tickets created on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter tickets created on or before this date (YYYY-MM-DD).
     * @param bool $include_merged Include tickets that were merged into other tickets (default: false).
     * @param string $sort Field to sort by. Useful values: edited (default), created, sla_deadtime, last_activity.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 100).
     */
    #[McpTool(name: 'list_account_tickets')]
    public function listAccountTickets(
        string $account,
        string $stage = 'OPEN',
        ?string $priority = null,
        ?string $user = null,
        ?string $category = null,
        ?string $date_from = null,
        ?string $date_to = null,
        bool $include_merged = false,
        string $sort = 'edited',
        string $sort_dir = 'desc',
        int $skip = 0,
        int $take = 100,
    ): CallToolResult {
        try {
            $priority = InputValidator::priority($priority);
            $sort_dir = InputValidator::sortDirection($sort_dir);
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
            $skip = InputValidator::skip($skip);
            $take = InputValidator::take($take, static::MAX_TAKE);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        $sort = SortFieldValidator::validate('tickets', $sort);

        // Step 1: Resolve account
        try {
            $accountData = $this->client->get('accounts', $account);
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }
        if ($accountData !== null) {
            $accountId = $accountData['name'];
            $accountTitle = $accountData['title'] ?? $accountId;
        } else {
            try {
                $searchResult = $this->client->list(
                    'accounts',
                    fieldFilters: [['title', 'like', $account]],
                    take: 1,
                );
            } catch (DaktelaApiException $e) {
                return self::formatApiError($e);
            }
            if ($searchResult['data'] === []) {
                return self::success("No account found matching '{$account}'.");
            }
            $accountId = $searchResult['data'][0]['name'];
            $accountTitle = $searchResult['data'][0]['title'] ?? $accountId;
        }

        // Step 2: Get contacts belonging to this account
        try {
            $contactsResult = $this->client->list(
                'contacts',
                fieldFilters: [['account', 'eq', $accountId]],
                take: static::MAX_TAKE,
                fields: ['name'],
            );
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }
        $contactNames = array_column($contactsResult['data'], 'name');
        if ($contactNames === []) {
            return self::success("Account: **{$accountTitle}** ({$accountId})\n\n"
                . 'No contacts found for this account, so no tickets can be retrieved.');
        }

        // Step 3: Query tickets with batched contacts
        $stageFilter = ($stage !== '' && strtoupper($stage) !== 'ALL') ? $stage : null;
        $ticketFilters = self::buildTicketFilters(
            category: $category,
            stage: $stageFilter,
            priority: $priority,
            user: $user,
            includeMerged: $include_merged,
            dateFrom: $date_from,
            dateTo: $date_to,
        );

        $batchSize = 50;
        $allTickets = [];
        $seen = [];
        $batches = array_chunk($contactNames, $batchSize);
        $maxBatches = 10;

        foreach (\array_slice($batches, 0, $maxBatches) as $batch) {
            $filters = array_merge($ticketFilters, [['contact', 'in', $batch]]);
            try {
                $result = $this->client->list(
                    'tickets',
                    fieldFilters: $filters,
                    skip: 0,
                    take: static::MAX_TAKE,
                    sort: $sort,
                    sortDir: $sort_dir,
                    fields: self::TICKET_LIST_FIELDS,
                );
            } catch (DaktelaApiException $e) {
                return self::formatApiError($e);
            }
            foreach ($result['data'] as $ticket) {
                $tid = $ticket['name'] ?? '';
                if (!isset($seen[$tid])) {
                    $seen[$tid] = true;
                    $allTickets[] = $ticket;
                }
            }
        }

        $total = \count($allTickets);
        $page = \array_slice($allTickets, $skip, $take);
        $header = "Account: **{$accountTitle}** ({$accountId})\n\n";

        $output = $header . TicketFormatter::formatList(
            $page,
            $total,
            $skip,
            $take,
            $this->client->getBaseUrl(),
        );

        if (\count($batches) > $maxBatches) {
            $output .= "\n\n(Results may be incomplete — account has >500 contacts. Only first 500 contacts' tickets are shown.)";
        }

        return self::success($output);
    }

    /**
     * List all ticket categories. Call this first to find valid category names for ticket filtering.
     * The 'name' field of each category is what you pass as the 'category' parameter in list_tickets/count_tickets.
     *
     * @param int $skip Pagination offset (default: 0).
     * @param int $take Number of records to return (default: 200).
     */
    #[McpTool(name: 'list_ticket_categories')]
    public function listTicketCategories(int $skip = 0, int $take = 200): CallToolResult
    {
        try {
            $result = $this->client->list('ticketsCategories', skip: $skip, take: $take);
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        return self::success(SimpleRecordFormatter::formatList(
            $result['data'],
            $result['total'],
            $skip,
            $take,
            'categories',
        ));
    }

    /**
     * @return list<array{string, string, string|list<string>}>
     */
    private static function buildTicketFilters(
        ?string $category = null,
        ?string $stage = null,
        ?string $priority = null,
        ?string $user = null,
        ?string $contact = null,
        ?string $search = null,
        ?string $status = null,
        bool $includeMerged = false,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $filters = FilterHelper::fromNullable([
            ['category', 'eq', $category],
            ['stage', 'eq', $stage],
            ['priority', 'eq', $priority],
            ['user', 'eq', $user],
            ['contact', 'eq', $contact],
            ['title', 'like', $search],
            ['statuses', 'eq', $status],
        ]);

        if (!$includeMerged) {
            $filters[] = ['id_merge', 'isnull', 'true'];
        }

        return array_merge($filters, DateFilterHelper::build('created', $dateFrom, $dateTo));
    }
}
