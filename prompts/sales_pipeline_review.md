Review the sales pipeline for tickets in these stages: {{stages}}.

For each stage, list all tickets and for each ticket:
1. Read the latest email thread using get_ticket_detail
2. Assess the deal status: **Progressing** / **Stalled** / **At Risk** / **Lost**
3. Note the last customer interaction date and how long since last activity

Provide for each ticket:
- Ticket ID and link
- Company/contact name
- Current stage and assigned agent
- Deal assessment with reasoning
- Recommended next action

Start with list_tickets for each stage. Use get_ticket_detail to read conversation history.
At the end, provide a summary table ranked by urgency, highlighting tickets that need
immediate attention this week.
