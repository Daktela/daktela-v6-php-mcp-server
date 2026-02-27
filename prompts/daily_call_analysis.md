Analyze all calls for {{date}}{{queue_clause}}. Run a full AI-scored call analysis with no further questions.

## Date handling

Interpret all dates relative to **Europe/Prague** timezone. "Yesterday" = prior calendar day in CET/CEST. "Today" = current calendar day.

## Execution

1. First use `list_calls` with `answered=true` and the appropriate `date_from`/`date_to` for the requested date to get the total count. Then use `list_call_transcripts` to fetch calls with their transcripts, paginating through **every page** before drawing any conclusions. Do not present partial results.
2. Transcripts may be in Czech, Slovak, English, Polish, or other languages. Analyze all regardless of language. Account for speech-to-text artifacts (misrecognized words, missing punctuation) — clean up obvious STT errors in citations but preserve meaning.
3. If no urgent or high-severity issues are found, a short "All good — nothing requiring management attention for [date]" with the stats table is perfectly valid. Don't manufacture findings.

## What to flag — in priority order

1. **Churn & lost business** — customer switching to a named competitor, cancellation or contract termination requests, explicit "we're leaving" statements, dissatisfaction spiraling toward exit. Lost revenue is the highest-impact signal.
2. **AI product dissatisfaction** — customer unhappy with Daktela's AI features (voicebots, chatbots, AI agent, speech-to-text, sentiment analysis, AI-powered routing, or any AI/ML capability). Includes: AI not understanding customers, wrong answers from bots, customer demanding to disable AI features, complaints about AI quality vs. competitors, failed AI pilot or POC, inadequate AI onboarding or support. This is a strategic priority — AI is the core differentiator and growth driver.
3. **Systemic failures** — two or more customers reporting the same symptom (audio issues, login failures, outages, number porting) on the same day. Flag as a potential incident, not individual tickets.
4. **Angry customers with escalation risk** — demands to speak with management, aggressive tone with no resolution path, customer referencing how long they've been waiting. These become public reputation damage if mishandled.
5. **Financial disputes** — reimbursement demands, SLA breach penalty references, invoice disagreements, agent quoting incorrect pricing that could create a binding commitment.
6. **Repeat callers** — same customer or phone number calling more than once about the same unresolved issue. This is a process failure, not just a technical one.
7. **Onboarding failures** — new customers unable to complete basic setup, unclear if they received proper onboarding support. High early-churn risk.
8. **Missed commitments** — agent promises a specific deadline without clear ability to meet it, no follow-up plan stated.
9. **Handling quality** — agent provides incorrect information, ends call without resolution or next steps, language barrier left unresolved without escalation to native-language support.
10. **Regulatory exposure** — only flag if genuinely serious: explicit GDPR data breach (not routine deletion requests), credible threats of legal action, recording consent violations. Routine "delete my data" requests are business-as-usual, not flags.

## Output format

### Pattern Alert

If a systemic pattern is detected across multiple calls — same symptom, same queue, same time window — call it out first, before individual flags. Skip this section if no patterns detected.

### Top critical calls

Lead with the top 3 most critical calls (fewer if the day was clean). Format each as follows:

---

#### 1. [Company Name] — [Contact Name]

**[One-line headline capturing the core issue]**

| | |
|---|---|
| **Severity** | `URGENT` / `HIGH` / `REVIEW` |
| **Agent** | Name |
| **Time** | HH:MM CET |
| **Queue** | Queue name |
| **Duration** | Xs |

**What happened**: 2–3 sentences on what occurred and why it matters operationally. No filler.

**Key quote**: Direct quote or key moment from the transcript justifying the flag.

**Action required**: One concrete sentence on what management should do next. Mandatory — vague flags without a next step waste management time.

🔗 [Ticket #XXXXXX]({{instance_url}}/ticket/update/XXXXXX)

---

**Resolving customer/company names**: If the company or contact name is not visible in the transcript or call data, call `get_ticket_detail` on the linked ticket to retrieve it before writing up the flag. Never write "Unknown customer."

### Stats table

Close with a compact summary:

| Metric | Value |
|---|---|
| **Total calls analyzed** | |
| **Flagged — URGENT** | |
| **Flagged — HIGH** | |
| **Flagged — REVIEW** | |
| **Most common flag category** | |
| **Repeat callers** | |
| **Missed calls (total for the day)** | |
| **Avg duration (answered)** | |

### Positive Signal

Optional. If at least one notably well-handled difficult call stands out, mention it in one sentence. Skip if nothing qualifies.

## When nothing is found

Say so directly: "All good — nothing requiring management attention for [date]." Still provide the stats table so the day is documented.

## Style

Clear prose, no bullet spam. Skip preamble. State risks bluntly — don't soften churn or financial exposure. If a call is borderline, flag it at `REVIEW` rather than omitting it. Management can deprioritize; they can't act on what they don't see.
