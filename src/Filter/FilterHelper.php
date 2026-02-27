<?php

declare(strict_types=1);

namespace Daktela\McpServer\Filter;

final class FilterHelper
{
    /**
     * Build a filter array from candidate tuples, dropping any where the value is null.
     *
     * @param list<array{string, string, string|list<string>|null}> $candidates
     * @return list<array{string, string, string|list<string>}>
     */
    public static function fromNullable(array $candidates): array
    {
        $filters = [];
        foreach ($candidates as [$field, $operator, $value]) {
            if ($value !== null) {
                $filters[] = [$field, $operator, $value];
            }
        }

        return $filters;
    }
}
