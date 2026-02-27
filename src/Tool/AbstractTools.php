<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tool;

use Daktela\McpServer\Client\DaktelaApiException;
use Daktela\McpServer\Client\DaktelaClientInterface;
use Daktela\McpServer\Filter\SortFieldValidator;
use Daktela\McpServer\Resolver\UserResolver;
use Daktela\McpServer\Validation\ValidationException;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

abstract class AbstractTools
{
    public const MAX_TAKE = 250;

    public function __construct(
        protected readonly DaktelaClientInterface $client,
    ) {}

    /**
     * Resolve a user parameter and return [loginName|originalInput, markdownHeader].
     * Header is empty string when user is null or not resolved.
     *
     * @return array{string|null, string}
     */
    protected function resolveUser(?string $user): array
    {
        if ($user === null) {
            return [null, ''];
        }

        [$loginName, $resolvedName] = UserResolver::resolve($this->client, $user);

        $header = $resolvedName !== null
            ? "Agent: **{$resolvedName}** ({$loginName})\n\n"
            : '';

        return [$loginName, $header];
    }

    /**
     * Execute a standard paginated list query and return formatted output.
     *
     * @param list<array{string, string, string|list<string>}> $filters
     * @param callable(list<array<string, mixed>>, int, int, int): string $formatter
     * @param list<string>|null $fields
     */
    protected function executeList(
        string $endpoint,
        array $filters,
        int $skip,
        int $take,
        string $sort,
        string $sortDir,
        string $header,
        callable $formatter,
        ?array $fields = null,
        ?string $search = null,
    ): CallToolResult {
        $take = min($take, static::MAX_TAKE);
        $sort = SortFieldValidator::validate($endpoint, $sort);

        try {
            $result = $this->client->list(
                $endpoint,
                fieldFilters: $filters !== [] ? $filters : null,
                skip: $skip,
                take: $take,
                sort: $sort,
                sortDir: $sortDir,
                fields: $fields,
                search: $search,
            );
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        return self::success($header . $formatter($result['data'], $result['total'], $skip, $take));
    }

    /**
     * Execute a count query and return a formatted total.
     *
     * @param list<array{string, string, string|list<string>}> $filters
     * @param array<string, string|null> $filterParts Label => value pairs for the description (null values are skipped).
     */
    protected function executeCount(
        string $endpoint,
        array $filters,
        string $entity,
        array $filterParts = [],
        ?string $search = null,
    ): CallToolResult {
        try {
            $result = $this->client->list(
                $endpoint,
                fieldFilters: $filters !== [] ? $filters : null,
                skip: 0,
                take: 1,
                fields: ['name'],
                search: $search,
            );
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        $parts = array_filter($filterParts, static fn($v) => $v !== null);
        $filterDesc = $parts !== [] ? ' matching [' . implode(', ', $parts) . ']' : '';

        return self::success("Total {$entity}{$filterDesc}: **{$result['total']}**");
    }

    /**
     * Fetch a single record or return a "not found" message.
     *
     * @param callable(array<string, mixed>): string $formatter
     */
    protected function executeGet(
        string $endpoint,
        string $name,
        string $entityLabel,
        callable $formatter,
    ): CallToolResult {
        try {
            $record = $this->client->get($endpoint, $name);
        } catch (DaktelaApiException $e) {
            return self::formatApiError($e);
        }

        if ($record === null) {
            return self::success("{$entityLabel} '{$name}' not found.");
        }

        return self::success($formatter($record));
    }

    protected static function formatValidationError(ValidationException $e): CallToolResult
    {
        return CallToolResult::error([new TextContent("Invalid parameter: {$e->getMessage()}")]);
    }

    protected static function formatApiError(DaktelaApiException $e): CallToolResult
    {
        $status = $e->httpStatus !== null ? " (HTTP {$e->httpStatus})" : '';
        $hint = self::errorHint($e->httpStatus, $e->endpoint);

        return CallToolResult::error([new TextContent("API error{$status}: {$e->getMessage()} [endpoint: {$e->endpoint}]{$hint}")]);
    }

    protected static function success(string $text): CallToolResult
    {
        return CallToolResult::success([new TextContent($text)]);
    }

    private static function errorHint(?int $httpStatus, string $endpoint): string
    {
        return match ($httpStatus) {
            401 => "\nHint: Authentication failed. Check that DAKTELA_USERNAME/DAKTELA_PASSWORD or DAKTELA_ACCESS_TOKEN are correct.",
            403 => "\nHint: Access denied to '{$endpoint}'. The configured user may lack read permission for this resource in Daktela admin.",
            404 => "\nHint: The endpoint '{$endpoint}' was not found. This may indicate an unsupported Daktela version or misconfigured URL.",
            429 => "\nHint: Rate limit exceeded. Wait a moment and retry with a smaller page size (lower 'take' value).",
            500, 502, 503 => "\nHint: Daktela server error. The instance may be temporarily unavailable. Try again shortly.",
            default => '',
        };
    }
}
