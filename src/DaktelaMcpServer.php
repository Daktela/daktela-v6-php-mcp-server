<?php

declare(strict_types=1);

namespace Daktela\McpServer;

use Daktela\McpServer\Client\DaktelaClientFactory;
use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Log\StderrJsonLogger;
use Daktela\McpServer\Prompt\DaktelaPrompts;
use Daktela\McpServer\Resource\DaktelaResources;
use Daktela\McpServer\Tool\AccountTools;
use Daktela\McpServer\Tool\ActivityTools;
use Daktela\McpServer\Tool\CallTools;
use Daktela\McpServer\Tool\CampaignTools;
use Daktela\McpServer\Tool\ContactTools;
use Daktela\McpServer\Tool\CrmTools;
use Daktela\McpServer\Tool\EmailTools;
use Daktela\McpServer\Tool\KnowledgeBaseTools;
use Daktela\McpServer\Tool\MessagingTools;
use Daktela\McpServer\Tool\ReferenceDataTools;
use Daktela\McpServer\Tool\TicketTools;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\Session\SessionStoreInterface;
use Mcp\Server\Transport\StdioTransport;
use Psr\Log\LoggerInterface;

final class DaktelaMcpServer
{
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function run(): void
    {
        $logger = $this->logger ?? new StderrJsonLogger();
        $client = $this->createClient($logger);
        $server = self::buildServer($client, $logger);

        $transport = new StdioTransport();
        $server->run($transport);
    }

    public static function buildServer(DaktelaClientInterface $client, LoggerInterface $logger, ?SessionStoreInterface $sessionStore = null): Server
    {
        $tickets = new TicketTools($client);
        $activities = new ActivityTools($client);
        $calls = new CallTools($client);
        $emails = new EmailTools($client);
        $messaging = new MessagingTools($client);
        $contacts = new ContactTools($client);
        $accounts = new AccountTools($client);
        $crm = new CrmTools($client);
        $campaigns = new CampaignTools($client);
        $refData = new ReferenceDataTools($client);
        $kb = new KnowledgeBaseTools($client);
        $prompts = new DaktelaPrompts($client->getBaseUrl());
        $resources = new DaktelaResources($client);

        $annotations = new ToolAnnotations(readOnlyHint: true, openWorldHint: true);

        $builder = Server::builder()
            ->setServerInfo(
                'Daktela',
                Version::get(),
                description: 'Read-only MCP server for the Daktela contact center REST API v6.',
                websiteUrl: 'https://github.com/Daktela/daktela-v6-php-mcp-server',
            )
            ->setInstructions(self::instructions())
            ->setLogger($logger);

        if ($sessionStore !== null) {
            $builder->setSession($sessionStore);
        }

        return $builder
            // Tools
            ->addTool($tickets->listTickets(...), 'list_tickets', annotations: $annotations)
            ->addTool($tickets->countTickets(...), 'count_tickets', annotations: $annotations)
            ->addTool($tickets->getTicket(...), 'get_ticket', annotations: $annotations)
            ->addTool($tickets->getTicketDetail(...), 'get_ticket_detail', annotations: $annotations)
            ->addTool($tickets->listAccountTickets(...), 'list_account_tickets', annotations: $annotations)
            ->addTool($tickets->listTicketCategories(...), 'list_ticket_categories', annotations: $annotations)
            ->addTool($activities->listActivities(...), 'list_activities', annotations: $annotations)
            ->addTool($activities->countActivities(...), 'count_activities', annotations: $annotations)
            ->addTool($activities->getActivity(...), 'get_activity', annotations: $annotations)
            ->addTool($calls->listCalls(...), 'list_calls', annotations: $annotations)
            ->addTool($calls->countCalls(...), 'count_calls', annotations: $annotations)
            ->addTool($calls->getCall(...), 'get_call', annotations: $annotations)
            ->addTool($calls->getCallTranscript(...), 'get_call_transcript', annotations: $annotations)
            ->addTool($calls->listCallTranscripts(...), 'list_call_transcripts', annotations: $annotations)
            ->addTool($emails->listEmails(...), 'list_emails', annotations: $annotations)
            ->addTool($emails->countEmails(...), 'count_emails', annotations: $annotations)
            ->addTool($emails->getEmail(...), 'get_email', annotations: $annotations)
            ->addTool($messaging->listChats(...), 'list_chats', annotations: $annotations)
            ->addTool($messaging->countChats(...), 'count_chats', annotations: $annotations)
            ->addTool($messaging->getChat(...), 'get_chat', annotations: $annotations)
            ->addTool($contacts->listContacts(...), 'list_contacts', annotations: $annotations)
            ->addTool($contacts->countContacts(...), 'count_contacts', annotations: $annotations)
            ->addTool($contacts->getContact(...), 'get_contact', annotations: $annotations)
            ->addTool($accounts->listAccounts(...), 'list_accounts', annotations: $annotations)
            ->addTool($accounts->countAccounts(...), 'count_accounts', annotations: $annotations)
            ->addTool($accounts->getAccount(...), 'get_account', annotations: $annotations)
            ->addTool($crm->listCrmRecords(...), 'list_crm_records', annotations: $annotations)
            ->addTool($crm->countCrmRecords(...), 'count_crm_records', annotations: $annotations)
            ->addTool($crm->getCrmRecord(...), 'get_crm_record', annotations: $annotations)
            ->addTool($campaigns->listCampaignRecords(...), 'list_campaign_records', annotations: $annotations)
            ->addTool($campaigns->countCampaignRecords(...), 'count_campaign_records', annotations: $annotations)
            ->addTool($campaigns->getCampaignRecord(...), 'get_campaign_record', annotations: $annotations)
            ->addTool($campaigns->listCampaignTypes(...), 'list_campaign_types', annotations: $annotations)
            ->addTool($refData->listQueues(...), 'list_queues', annotations: $annotations)
            ->addTool($refData->listUsers(...), 'list_users', annotations: $annotations)
            ->addTool($refData->listGroups(...), 'list_groups', annotations: $annotations)
            ->addTool($refData->listStatuses(...), 'list_statuses', annotations: $annotations)
            ->addTool($refData->listPauses(...), 'list_pauses', annotations: $annotations)
            ->addTool($refData->listTemplates(...), 'list_templates', annotations: $annotations)
            ->addTool($refData->listRealtimeSessions(...), 'list_realtime_sessions', annotations: $annotations)
            ->addTool($kb->listArticles(...), 'list_articles', annotations: $annotations)
            ->addTool($kb->getArticle(...), 'get_article', annotations: $annotations)
            ->addTool($kb->listArticleFolders(...), 'list_article_folders', annotations: $annotations)
            // Prompts
            ->addPrompt($prompts->emailQualityAudit(...), 'email_quality_audit')
            ->addPrompt($prompts->salesPipelineReview(...), 'sales_pipeline_review')
            ->addPrompt($prompts->callQualityReview(...), 'call_quality_review')
            ->addPrompt($prompts->dailyCallAnalysis(...), 'daily_call_analysis')
            // Resources
            ->addResource($resources->instanceInfo(...), uri: 'daktela://instance', name: 'instance_info', description: 'Connected Daktela instance URL and server version.', mimeType: 'application/json')
            ->addResource($resources->fieldSchema(...), uri: 'daktela://schema', name: 'field_schema', description: 'Field definitions, entity relationships, and valid filter values.', mimeType: 'application/json')
            ->build();
    }

    private function createClient(?LoggerInterface $logger = null): DaktelaClientInterface
    {
        return (new DaktelaClientFactory(logger: $logger))->create();
    }

    private static function instructions(): string
    {
        return <<<'INSTRUCTIONS'
            Read-only access to the Daktela contact center platform (REST API v6).

            ## Naming conventions
            - Every record has a `name` field (internal unique ID) and a `title` field (human display name).
            - **user/agent filters**: pass the LOGIN NAME (`name` field from list_users), e.g. 'john.doe'. Tools that accept a 'user' parameter also accept display names like 'John Doe' — these are resolved automatically.
            - **contact filters**: pass the contact's internal `name` ID (e.g. 'contact_674eda46162a8403430453'). Use list_contacts(search=...) to find the ID.
            - **account filters**: pass the account's internal `name` ID. Use list_accounts(search=...) to find the ID. Exception: list_account_tickets accepts a human-readable company name directly.
            - **category/queue filters**: pass the `name` field from list_ticket_categories / list_queues.

            ## Field values

            **Ticket stage**: OPEN (agent working on it) | WAIT (waiting for customer) | CLOSE (resolved) | ARCHIVE (resolved, new reply creates fresh ticket)

            **Ticket priority**: LOW | MEDIUM | HIGH

            **Activity type**: CALL | EMAIL | CHAT | SMS | FBM (Facebook Messenger) | IGDM (Instagram DM) | WAP (WhatsApp) | VBR (Viber) | CUSTOM

            **Activity action**: OPEN (in progress) | WAIT | POSTPONE | CLOSE

            **Call direction**: in | out | internal

            ## Entity relationships
            - Accounts are companies/organizations. Contacts belong to accounts.
            - CRM records are deals/opportunities linked to contacts, accounts, and tickets.
            - Campaign records track outbound campaign activity.

            ## Important notes
            - Dates in YYYY-MM-DD or 'YYYY-MM-DD HH:MM:SS' format.
            - Pagination: use skip + take. Default take=100, max take=250.
            - Custom fields are instance-specific and returned automatically in tool responses.
            - Use count_* tools (count_tickets, count_activities, count_calls, count_emails, count_chats, count_contacts, count_accounts, count_crm_records, count_campaign_records) instead of list_* when you only need a count.
            - Use get_ticket_detail to get a ticket with all its linked activities in one call.
            INSTRUCTIONS;
    }
}
