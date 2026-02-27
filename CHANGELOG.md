# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-02-27

### Added
- 43 read-only tools: tickets, activities, calls, emails, messaging, contacts, accounts, CRM, campaigns, reference data, real-time sessions, knowledge base
- Count tools for all list endpoints (`count_tickets`, `count_activities`, `count_calls`, `count_emails`, `count_chats`, `count_contacts`, `count_accounts`, `count_crm_records`, `count_campaign_records`)
- Detail retrieval tools (`get_ticket`, `get_ticket_detail`, `get_activity`, `get_call`, `get_call_transcript`, `get_email`, `get_chat`, `get_contact`, `get_account`, `get_crm_record`, `get_campaign_record`, `get_article`)
- Input validation for enum parameters (stage, priority, sort direction)
- Structured JSON logging to stderr for tool calls
- 4 MCP prompt templates: email quality audit, sales pipeline review, call quality review, daily call analysis
- MCP resources exposing instance info and field schema
- Health endpoint proxying `/api/v6/whoami.json`
- CORS headers for HTTP mode
- OAuth 2.0 provider for remote MCP deployment
- In-memory TTL cache for reference data
- User name resolution (display name to login name)
- Markdown-formatted output for LLM readability
- Actionable error messages with troubleshooting guidance
- Production-ready Dockerfile with multi-stage build
- GitHub Actions CI pipeline (PHPUnit, PHPStan, CS Fixer, security audit)
- Comprehensive unit tests for all tool classes
- `.env.example` with documented configuration options
- `.dockerignore` for optimized Docker builds
- Server version sourced from `composer.json`
