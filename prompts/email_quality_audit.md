Analyze all emails from the {{period}}{{queue_clause}}. For each email, read the full conversation thread.

Flag any interactions that show:
1. **Negative customer sentiment** — frustration, complaints, threats to leave
2. **Lost deals** — customer chose a competitor, cancelled, or declined
3. **Unprofessional agent tone** — rude, dismissive, overly casual, or unhelpful responses
4. **Unresolved issues** — customer repeated their question without getting a proper answer

For each flagged email, provide:
- Ticket ID and link
- Agent name
- Customer email/name
- Category of issue (sentiment/lost deal/tone/unresolved)
- Brief description of what went wrong
- Suggested corrective action

Start by using list_emails to get recent emails, then use get_ticket_detail for flagged tickets.
At the end, provide a summary with counts per category and per agent.
