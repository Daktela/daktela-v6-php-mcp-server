<?php

declare(strict_types=1);

namespace Daktela\McpServer\Resource;

use Daktela\McpServer\Client\DaktelaClientInterface;
use Mcp\Capability\Attribute\McpResource;

final class DaktelaResources
{
    public function __construct(
        private readonly DaktelaClientInterface $client,
    ) {}

    /**
     * @return string JSON describing the connected Daktela instance
     */
    #[McpResource(
        uri: 'daktela://instance',
        name: 'instance_info',
        description: 'Connected Daktela instance URL and server version.',
        mimeType: 'application/json',
    )]
    public function instanceInfo(): string
    {
        return json_encode([
            'url' => $this->client->getBaseUrl(),
            'api_version' => 'v6',
            'access' => 'read-only',
        ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT);
    }

    /**
     * @return string JSON schema documentation for Daktela entities
     */
    #[McpResource(
        uri: 'daktela://schema',
        name: 'field_schema',
        description: 'Field definitions, entity relationships, and valid filter values for all Daktela entities.',
        mimeType: 'application/json',
    )]
    public function fieldSchema(): string
    {
        return json_encode([
            'entities' => [
                'ticket' => [
                    'key_fields' => ['name (ID)', 'title (display name)', 'stage', 'priority', 'category', 'user', 'contact', 'description'],
                    'stages' => ['OPEN', 'WAIT', 'CLOSE', 'ARCHIVE'],
                    'priorities' => ['LOW', 'MEDIUM', 'HIGH'],
                    'date_fields' => ['created', 'edited', 'last_activity', 'sla_deadtime', 'sla_close_deadline'],
                    'relationships' => ['contact (belongs to)', 'user (assigned agent)', 'category', 'statuses'],
                ],
                'activity' => [
                    'key_fields' => ['name (ID)', 'type', 'action', 'queue', 'user', 'ticket', 'time'],
                    'types' => ['CALL', 'EMAIL', 'CHAT', 'SMS', 'FBM', 'IGDM', 'WAP', 'VBR', 'CUSTOM'],
                    'actions' => ['OPEN', 'WAIT', 'POSTPONE', 'CLOSE'],
                    'relationships' => ['ticket (parent)', 'user (agent)', 'queue'],
                ],
                'call' => [
                    'key_fields' => ['id_call', 'call_time', 'direction', 'answered', 'id_queue', 'id_agent', 'duration'],
                    'directions' => ['in', 'out', 'internal'],
                    'date_fields' => ['call_time'],
                ],
                'email' => [
                    'key_fields' => ['name', 'queue', 'user', 'title', 'address', 'direction', 'time'],
                    'directions' => ['in', 'out'],
                    'date_fields' => ['time'],
                ],
                'contact' => [
                    'key_fields' => ['name (ID)', 'title', 'firstname', 'lastname', 'email', 'phone', 'account', 'user'],
                    'relationships' => ['account (company)', 'user (owner)'],
                ],
                'account' => [
                    'key_fields' => ['name (ID)', 'title (company name)'],
                    'relationships' => ['contacts (has many)'],
                ],
                'crm_record' => [
                    'key_fields' => ['name', 'title', 'type', 'user', 'contact', 'account'],
                    'relationships' => ['contact', 'account', 'user (owner)'],
                ],
            ],
            'conventions' => [
                'name_field' => 'Internal unique ID, used for API filters',
                'title_field' => 'Human-readable display name',
                'date_format' => 'YYYY-MM-DD or YYYY-MM-DD HH:MM:SS',
                'pagination' => 'skip (offset) + take (limit), max take=1000',
            ],
        ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT);
    }
}
