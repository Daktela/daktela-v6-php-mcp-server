<?php

declare(strict_types=1);

namespace Daktela\McpServer\Prompt;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;

final class DaktelaPrompts
{
    private const PROMPTS_DIR = __DIR__ . '/../../prompts';

    public function __construct(
        private readonly string $instanceUrl = '',
    ) {}

    /**
     * Audit email quality across all queues for a given time period.
     *
     * @param string $period Time period to analyze, e.g. 'last 72 hours', 'last week', '2026-02-01 to 2026-02-25'.
     * @param string|null $queue Optional queue name to focus on.
     * @return list<PromptMessage>
     */
    #[McpPrompt(
        name: 'email_quality_audit',
        description: 'Audit email quality — flags negative sentiment, unprofessional tone, and lost deals in recent emails.',
    )]
    public function emailQualityAudit(string $period = 'last 72 hours', ?string $queue = null): array
    {
        return self::render('email_quality_audit', [
            'period' => $period,
            'queue_clause' => $queue !== null ? " in the '{$queue}' queue" : '',
        ]);
    }

    /**
     * Review the sales pipeline and assess deal health.
     *
     * @param string $stages Comma-separated ticket stages or statuses to review, e.g. 'OPEN,WAIT' or 'S1-Discovery,S2-Qualification'.
     * @return list<PromptMessage>
     */
    #[McpPrompt(
        name: 'sales_pipeline_review',
        description: 'Review open sales tickets, assess deal health, and recommend actions for at-risk opportunities.',
    )]
    public function salesPipelineReview(string $stages = 'OPEN,WAIT'): array
    {
        return self::render('sales_pipeline_review', [
            'stages' => $stages,
        ]);
    }

    /**
     * Review call transcripts for quality and coaching opportunities.
     *
     * @param string $period Time period to analyze, e.g. 'last week', 'last 3 days'.
     * @param string|null $user Optional agent name to focus on.
     * @return list<PromptMessage>
     */
    #[McpPrompt(
        name: 'call_quality_review',
        description: 'Review call transcripts to identify escalations, knowledge gaps, and coaching opportunities.',
    )]
    public function callQualityReview(string $period = 'last week', ?string $user = null): array
    {
        return self::render('call_quality_review', [
            'period' => $period,
            'user_clause' => $user !== null ? " for agent '{$user}'" : '',
        ]);
    }

    /**
     * AI-scored daily call analysis for management. Flags churn, AI dissatisfaction, systemic failures, and handling issues.
     *
     * @param string $date The date to analyze, e.g. 'yesterday', '2026-02-25'. Interpreted in Europe/Prague timezone.
     * @param string|null $queue Optional queue name to focus on.
     * @return list<PromptMessage>
     */
    #[McpPrompt(
        name: 'daily_call_analysis',
        description: 'Full AI-scored daily call analysis — flags churn risk, AI product issues, systemic failures, and handling quality for management review.',
    )]
    public function dailyCallAnalysis(string $date = 'yesterday', ?string $queue = null): array
    {
        return self::render('daily_call_analysis', [
            'date' => $date,
            'queue_clause' => $queue !== null ? " in the '{$queue}' queue" : '',
            'instance_url' => rtrim($this->instanceUrl, '/'),
        ]);
    }

    /**
     * @param array<string, string> $vars
     * @return list<PromptMessage>
     */
    private static function render(string $name, array $vars): array
    {
        $path = self::PROMPTS_DIR . "/{$name}.md";
        $template = file_get_contents($path);

        if ($template === false) {
            throw new \RuntimeException("Prompt template not found: {$path}");
        }

        $text = str_replace(
            array_map(fn(string $k) => "{{{$k}}}", array_keys($vars)),
            array_values($vars),
            $template,
        );

        return [
            new PromptMessage(
                role: Role::User,
                content: new TextContent($text),
            ),
        ];
    }
}
