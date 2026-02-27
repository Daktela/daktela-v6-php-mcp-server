<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Filter\DateFilterHelper;
use Daktela\McpServer\Filter\FilterHelper;
use Daktela\McpServer\Formatter\ChatFormatter;
use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;

final class MessagingTools extends AbstractTools
{
    /**
     * @var array<string, array{endpoint: string, entity: string, formatter: string, hasDirection: bool}>
     */
    private const CHANNEL_CONFIG = [
        'webchat'   => ['endpoint' => 'activitiesWeb',  'entity' => 'web chats',   'formatter' => 'chat',      'hasDirection' => false],
        'sms'       => ['endpoint' => 'activitiesSms',  'entity' => 'SMS chats',   'formatter' => 'chat',      'hasDirection' => true],
        'messenger' => ['endpoint' => 'activitiesFbm',  'entity' => 'Messenger',   'formatter' => 'chat',      'hasDirection' => true],
        'instagram' => ['endpoint' => 'activitiesIgdm', 'entity' => 'Instagram',   'formatter' => 'instagram', 'hasDirection' => true],
        'whatsapp'  => ['endpoint' => 'activitiesWap',  'entity' => 'WhatsApp',    'formatter' => 'chat',      'hasDirection' => true],
        'viber'     => ['endpoint' => 'activitiesVbr',  'entity' => 'Viber',       'formatter' => 'chat',      'hasDirection' => true],
    ];

    /**
     * List chats for a messaging channel. Returns one page of results.
     *
     * @param string $channel Channel to query: webchat, sms, messenger, instagram, whatsapp, viber.
     * @param string|null $queue Filter by queue internal name (use list_queues to find valid names).
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID. NOT a person's name — call list_contacts(search='...') first to find the ID.
     * @param string|null $direction Filter by direction: in (incoming) or out (outgoing). Accepted on all channels, silently ignored for webchat.
     * @param string|null $date_from Filter chats on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter chats on or before this date (YYYY-MM-DD).
     * @param string $sort Field to sort by. Useful values: time (default), duration, wait_time.
     * @param string $sort_dir Sort direction: asc or desc (default: desc).
     * @param int $skip Number of records to skip for pagination (default: 0).
     * @param int $take Number of records to return (default: 100, max: 250).
     */
    #[McpTool(name: 'list_chats')]
    public function listChats(
        #[CompletionProvider(values: ['webchat', 'sms', 'messenger', 'instagram', 'whatsapp', 'viber'])]
        string $channel,
        ?string $queue = null,
        ?string $user = null,
        ?string $contact = null,
        ?string $direction = null,
        ?string $date_from = null,
        ?string $date_to = null,
        string $sort = 'time',
        string $sort_dir = 'desc',
        int $skip = 0,
        int $take = 100,
    ): CallToolResult {
        $config = self::CHANNEL_CONFIG[strtolower($channel)] ?? null;
        if ($config === null) {
            return self::success("Unknown channel '{$channel}'. Valid channels: " . implode(', ', array_keys(self::CHANNEL_CONFIG)) . '.');
        }

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

        if (!$config['hasDirection']) {
            $direction = null;
        }

        return $this->listChannelChats(
            endpoint: $config['endpoint'],
            entity: $config['entity'],
            channel: $config['formatter'],
            queue: $queue,
            user: $user,
            contact: $contact,
            direction: $direction,
            dateFrom: $date_from,
            dateTo: $date_to,
            sort: $sort,
            sortDir: $sort_dir,
            skip: $skip,
            take: $take,
        );
    }

    /**
     * Count chats for a messaging channel. Use this instead of list_chats when you only need a number.
     *
     * @param string $channel Channel to query: webchat, sms, messenger, instagram, whatsapp, viber.
     * @param string|null $queue Filter by queue internal name (use list_queues to find valid names).
     * @param string|null $user Agent name — pass either a display name (e.g. 'John Doe') or login name (e.g. 'john.doe'). Display names are resolved automatically.
     * @param string|null $contact Filter by contact internal ID. NOT a person's name — call list_contacts(search='...') first to find the ID.
     * @param string|null $direction Filter by direction: in (incoming) or out (outgoing). Accepted on all channels, silently ignored for webchat.
     * @param string|null $date_from Filter chats on or after this date (YYYY-MM-DD).
     * @param string|null $date_to Filter chats on or before this date (YYYY-MM-DD).
     */
    #[McpTool(name: 'count_chats')]
    public function countChats(
        #[CompletionProvider(values: ['webchat', 'sms', 'messenger', 'instagram', 'whatsapp', 'viber'])]
        string $channel,
        ?string $queue = null,
        ?string $user = null,
        ?string $contact = null,
        ?string $direction = null,
        ?string $date_from = null,
        ?string $date_to = null,
    ): CallToolResult {
        $config = self::CHANNEL_CONFIG[strtolower($channel)] ?? null;
        if ($config === null) {
            return self::success("Unknown channel '{$channel}'. Valid channels: " . implode(', ', array_keys(self::CHANNEL_CONFIG)) . '.');
        }

        try {
            $direction = InputValidator::direction($direction);
            $date_from = InputValidator::date($date_from);
            $date_to = InputValidator::date($date_to);
        } catch (ValidationException $e) {
            return self::formatValidationError($e);
        }

        if (!$config['hasDirection']) {
            $direction = null;
        }

        [$user, ] = $this->resolveUser($user);

        $filters = FilterHelper::fromNullable([
            ['queue', 'eq', $queue],
            ['user', 'eq', $user],
            ['contact', 'eq', $contact],
            ['direction', 'eq', $direction],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('time', $date_from, $date_to));

        return $this->executeCount($config['endpoint'], $filters, $config['entity'], [
            'channel' => "channel={$channel}",
            'queue' => $queue !== null ? "queue={$queue}" : null,
            'user' => $user !== null ? "user={$user}" : null,
            'contact' => $contact !== null ? "contact={$contact}" : null,
            'direction' => $direction !== null ? "direction={$direction}" : null,
            'date_from' => $date_from !== null ? "from {$date_from}" : null,
            'date_to' => $date_to !== null ? "to {$date_to}" : null,
        ]);
    }

    /**
     * Get full details of a single chat by its name/ID.
     *
     * @param string $channel Channel to query: webchat, sms, messenger, instagram, whatsapp, viber.
     * @param string $name The chat internal name/ID.
     */
    #[McpTool(name: 'get_chat')]
    public function getChat(string $channel, string $name): CallToolResult
    {
        $config = self::CHANNEL_CONFIG[strtolower($channel)] ?? null;
        if ($config === null) {
            return self::success("Unknown channel '{$channel}'. Valid channels: " . implode(', ', array_keys(self::CHANNEL_CONFIG)) . '.');
        }

        return $this->getChannelRecord($config['endpoint'], $name, $config['entity'], $config['formatter']);
    }

    private function listChannelChats(
        string $endpoint,
        string $entity,
        string $channel,
        ?string $queue,
        ?string $user,
        ?string $contact,
        ?string $direction,
        ?string $dateFrom,
        ?string $dateTo,
        string $sort,
        string $sortDir,
        int $skip,
        int $take,
    ): CallToolResult {
        [$user, $header] = $this->resolveUser($user);

        $filters = FilterHelper::fromNullable([
            ['queue', 'eq', $queue],
            ['user', 'eq', $user],
            ['contact', 'eq', $contact],
            ['direction', 'eq', $direction],
        ]);
        $filters = array_merge($filters, DateFilterHelper::build('time', $dateFrom, $dateTo));

        return $this->executeList(
            $endpoint,
            $filters,
            $skip,
            $take,
            $sort,
            $sortDir,
            $header,
            fn($data, $total, $s, $t) => ChatFormatter::formatList(
                $data,
                $total,
                $s,
                $t,
                baseUrl: $this->client->getBaseUrl(),
                entity: $entity,
                channel: $channel,
            ),
        );
    }

    private function getChannelRecord(string $endpoint, string $name, string $entity, string $channel): CallToolResult
    {
        return $this->executeGet(
            $endpoint,
            $name,
            $entity,
            fn($record) => ChatFormatter::format($record, baseUrl: $this->client->getBaseUrl(), channel: $channel),
        );
    }
}
