<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Client\DaktelaApiException;
use Daktela\McpServer\Formatter\RealtimeSessionFormatter;
use Daktela\McpServer\Formatter\SimpleRecordFormatter;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class ReferenceDataTools extends AbstractTools
{
    /**
     * List all queues. Returns queue names and titles for use as filter values in other tools.
     *
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 200, max: 250).
     */
    #[McpTool(name: 'list_queues')]
    public function listQueues(int $skip = 0, int $take = 200): CallToolResult
    {
        return $this->listSimple('queues', 'queues', $skip, $take);
    }

    /**
     * List all users (agents). Returns user login names and display names for use as filter values in other tools.
     *
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 200, max: 250).
     */
    #[McpTool(name: 'list_users')]
    public function listUsers(int $skip = 0, int $take = 200): CallToolResult
    {
        return $this->listSimple('users', 'users', $skip, $take);
    }

    /**
     * List all groups. Returns group names and titles.
     *
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 200, max: 250).
     */
    #[McpTool(name: 'list_groups')]
    public function listGroups(int $skip = 0, int $take = 200): CallToolResult
    {
        return $this->listSimple('groups', 'groups', $skip, $take);
    }

    /**
     * List all statuses. Returns status names and titles for use as filter values in ticket tools.
     *
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 200, max: 250).
     */
    #[McpTool(name: 'list_statuses')]
    public function listStatuses(int $skip = 0, int $take = 200): CallToolResult
    {
        return $this->listSimple('statuses', 'statuses', $skip, $take);
    }

    /**
     * List all pauses. Returns pause names and titles.
     *
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 200, max: 250).
     */
    #[McpTool(name: 'list_pauses')]
    public function listPauses(int $skip = 0, int $take = 200): CallToolResult
    {
        return $this->listSimple('pauses', 'pauses', $skip, $take);
    }

    /**
     * List all templates. Returns template names and titles.
     *
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 200, max: 250).
     */
    #[McpTool(name: 'list_templates')]
    public function listTemplates(int $skip = 0, int $take = 200): CallToolResult
    {
        return $this->listSimple('templates', 'templates', $skip, $take);
    }

    /**
     * List current realtime sessions. Shows active agent sessions with their state, queue, and direction.
     *
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 200, max: 250).
     */
    #[McpTool(name: 'list_realtime_sessions')]
    public function listRealtimeSessions(int $skip = 0, int $take = 200): CallToolResult
    {
        return $this->listSimple(
            'realtimeSessions',
            'realtime sessions',
            $skip,
            $take,
            fn($data, $total, $s, $t) => RealtimeSessionFormatter::formatList($data, $total, $s, $t),
        );
    }

    private function listSimple(string $endpoint, string $entity, int $skip, int $take, ?callable $formatter = null): CallToolResult
    {
        $take = min($take, static::MAX_TAKE);

        try {
            $result = $this->client->list($endpoint, skip: $skip, take: $take);
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        $fmt = $formatter ?? fn($data, $total, $s, $t) => SimpleRecordFormatter::formatList($data, $total, $s, $t, $entity);

        return self::success($fmt($result['data'], $result['total'], $skip, $take));
    }
}
