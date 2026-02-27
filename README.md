# daktela-v6-php-mcp-server

> **Beta**: This project is in early beta. APIs, tool names, and output formats may change in future releases.

Read-only [MCP](https://modelcontextprotocol.io/) server for the [Daktela](https://www.daktela.com/) contact center REST API v6. Gives any MCP-compatible LLM client (Claude, Cursor, etc.) full read access to tickets, calls, emails, chats, contacts, CRM records, campaigns, and real-time agent status.

## Why

A contact center generates thousands of interactions daily — calls, emails, chats across multiple channels. The raw data is all in Daktela, but extracting insight from it requires either manual review or custom reporting. This server bridges Daktela to an LLM, letting you run analysis that would be impractical to do by hand:

- **Email quality audit** — flag negative sentiment, unprofessional tone, and lost deals across all recent emails
- **Call transcript analysis** — identify escalations, knowledge gaps, and unmet commitments from call recordings
- **Sales pipeline review** — assess deal health across ticket stages, rank by urgency, and recommend actions

The LLM reads the actual conversation content — email bodies, chat messages, call transcripts — and applies judgment that no dashboard or filter can replicate.

## Tools

43 read-only tools organized by domain:

| Category | Tools |
|---|---|
| **Tickets** | `count_tickets`, `get_ticket`, `get_ticket_detail`, `list_account_tickets`, `list_ticket_categories`, `list_tickets` |
| **Activities** | `count_activities`, `get_activity`, `list_activities` |
| **Calls** | `count_calls`, `get_call`, `get_call_transcript`, `list_call_transcripts`, `list_calls` |
| **Emails** | `count_emails`, `get_email`, `list_emails` |
| **Messaging** | `count_chats`, `get_chat`, `list_chats` (supports webchat, SMS, Messenger, Instagram, WhatsApp, Viber) |
| **Contacts & CRM** | `count_accounts`, `count_contacts`, `count_crm_records`, `get_account`, `get_contact`, `get_crm_record`, `list_accounts`, `list_contacts`, `list_crm_records` |
| **Campaigns** | `count_campaign_records`, `get_campaign_record`, `list_campaign_records`, `list_campaign_types` |
| **Reference data** | `list_groups`, `list_pauses`, `list_queues`, `list_statuses`, `list_templates`, `list_users` |
| **Real-time** | `list_realtime_sessions` |
| **Knowledge base** | `get_article`, `list_article_folders`, `list_articles` |

All list tools support pagination (`skip`, `take`), sorting, and contextual filters (stage, priority, date range, user, queue, etc.). Input parameters are validated against enum values with clear error messages.

## Prompts

4 built-in prompt templates for common analysis workflows:

| Prompt | Description |
|---|---|
| `email_quality_audit` | Audit email quality — flags negative sentiment, unprofessional tone, and lost deals |
| `sales_pipeline_review` | Review open sales tickets, assess deal health, and recommend actions |
| `call_quality_review` | Review call transcripts for escalations, knowledge gaps, and coaching opportunities |
| `daily_call_analysis` | Full AI-scored daily call analysis — flags churn risk, AI product issues, and handling quality |

## Resources

| Resource | URI | Description |
|---|---|---|
| Instance info | `daktela://instance` | Connected Daktela instance URL and API version |
| Field schema | `daktela://schema` | Entity definitions, relationships, and valid filter values |

## Getting started

### Prerequisites

- PHP 8.2+
- Composer
- A Daktela instance with a user account that has API read access

### Install

```bash
git clone https://github.com/Daktela/daktela-v6-php-mcp-server.git
cd daktela-v6-php-mcp-server
composer install
```

### Run with Claude Desktop

Add to your Claude Desktop configuration (`claude_desktop_config.json`):

**With Docker (recommended):**

```json
{
  "mcpServers": {
    "daktela": {
      "command": "docker",
      "args": [
        "run", "-i", "--rm",
        "-e", "DAKTELA_URL=https://your-instance.daktela.com",
        "-e", "DAKTELA_ACCESS_TOKEN=your-api-token",
        "daktela-v6-php-mcp-server"
      ]
    }
  }
}
```

**With PHP directly:**

```json
{
  "mcpServers": {
    "daktela": {
      "command": "php",
      "args": ["/path/to/daktela-v6-php-mcp-server/bin/server.php"],
      "env": {
        "DAKTELA_URL": "https://your-instance.daktela.com",
        "DAKTELA_ACCESS_TOKEN": "your-api-token"
      }
    }
  }
}
```

### Run with Docker

```bash
# Build the production image
docker build -f Dockerfile.prod -t daktela-v6-php-mcp-server .

# Run with stdio transport
docker run -i --rm \
  -e DAKTELA_URL=https://your-instance.daktela.com \
  -e DAKTELA_ACCESS_TOKEN=your-api-token \
  daktela-v6-php-mcp-server

# Run HTTP server (for remote deployments)
docker run --rm -p 8080:8080 \
  -e DAKTELA_URL=https://your-instance.daktela.com \
  -e DAKTELA_ACCESS_TOKEN=your-api-token \
  daktela-v6-php-mcp-server php bin/http-server.php
```

### Deploy as HTTP server

For shared or remote deployments (e.g., Cloud Run), the server runs in streamable-http mode where each client passes credentials via HTTP headers:

```bash
gcloud run deploy daktela-v6-php-mcp-server \
  --source . \
  --dockerfile Dockerfile.prod \
  --region europe-west1 \
  --allow-unauthenticated \
  --memory 1Gi
```

## Architecture

```
src/
├── DaktelaMcpServer.php       Main server — registers tools, prompts, resources
├── Version.php                Version from composer.json
├── Auth/                      Authentication & URL validation
├── Cache/                     Reference data caching (TTL)
├── Client/                    Daktela API client wrapper
├── Config/                    Configuration resolution (env vars)
├── Enum/                      PHP enums (TicketStage, Priority, Direction, etc.)
├── Filter/                    Query filter & date range builders
├── Formatter/                 Markdown output formatters per entity
├── Http/                      HTTP request handler (CORS, health, routing)
├── Log/                       Structured JSON logger (stderr)
├── Prompt/                    MCP prompt templates
├── Resolver/                  Name resolution (users, folders, tags)
├── Resource/                  MCP resources (instance info, schema)
├── Session/                   Session store for HTTP mode
├── Tool/                      11 tool classes (tickets, calls, emails, etc.)
└── Validation/                Input validation using PHP enums
```

## Configuration

| Environment variable | Default | Description |
|---|---|---|
| `DAKTELA_URL` | — | Daktela instance URL (required) |
| `DAKTELA_ACCESS_TOKEN` | — | API access token (required) |
| `CACHE_ENABLED` | `true` | Enable reference data cache |
| `CACHE_TTL_SECONDS` | `3600` | Cache TTL in seconds |

See `.env.example` for a template.

## Development

```bash
# Run in Docker (as per project convention)
docker compose run php composer install
docker compose run php composer test
docker compose run php composer analyse
docker compose run php vendor/bin/php-cs-fixer fix --dry-run --diff
```

## License

Proprietary. Requires an active [Daktela](https://www.daktela.com/) Contact Center license. See [LICENSE](LICENSE) for details.
