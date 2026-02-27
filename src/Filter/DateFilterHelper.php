<?php

declare(strict_types=1);

namespace Daktela\McpServer\Filter;

final class DateFilterHelper
{
    /**
     * Build date range filter tuples.
     *
     * Daktela expects 'YYYY-MM-DD HH:MM:SS'. A bare date like '2026-02-17'
     * is treated as midnight (00:00:00), so date_to without a time component
     * would exclude everything that happened during that day. We append
     * ' 23:59:59' to date_to when no time is present.
     *
     * @return list<array{string, string, string}>
     */
    public static function build(string $field, ?string $dateFrom, ?string $dateTo): array
    {
        $filters = [];

        if ($dateFrom !== null && $dateFrom !== '') {
            $filters[] = [$field, 'gte', str_replace('T', ' ', $dateFrom)];
        }

        if ($dateTo !== null && $dateTo !== '') {
            $normalized = str_replace('T', ' ', $dateTo);
            if (\strlen($normalized) === 10) {
                $normalized .= ' 23:59:59';
            }
            $filters[] = [$field, 'lte', $normalized];
        }

        return $filters;
    }
}
