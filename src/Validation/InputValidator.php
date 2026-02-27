<?php

declare(strict_types=1);

namespace Daktela\McpServer\Validation;

use Daktela\McpServer\Enum\ActivityAction;
use Daktela\McpServer\Enum\ActivityType;
use Daktela\McpServer\Enum\Direction;
use Daktela\McpServer\Enum\SortDirection;
use Daktela\McpServer\Enum\TicketPriority;
use Daktela\McpServer\Enum\TicketStage;
use Daktela\McpServer\Tool\AbstractTools;

final class InputValidator
{
    /**
     * Validate and normalize a ticket stage value.
     * Returns null if the input is null, throws on invalid input.
     */
    public static function stage(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $upper = strtoupper(trim($value));
        $valid = TicketStage::tryFrom($upper);
        if ($valid === null) {
            $allowed = implode(', ', array_column(TicketStage::cases(), 'value'));
            throw new ValidationException("Invalid ticket stage '{$value}'. Valid values: {$allowed}");
        }

        return $valid->value;
    }

    /**
     * Validate and normalize a ticket priority value.
     */
    public static function priority(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $upper = strtoupper(trim($value));
        $valid = TicketPriority::tryFrom($upper);
        if ($valid === null) {
            $allowed = implode(', ', array_column(TicketPriority::cases(), 'value'));
            throw new ValidationException("Invalid priority '{$value}'. Valid values: {$allowed}");
        }

        return $valid->value;
    }

    /**
     * Validate and normalize a sort direction value.
     */
    public static function sortDirection(string $value): string
    {
        $lower = strtolower(trim($value));
        $valid = SortDirection::tryFrom($lower);
        if ($valid === null) {
            throw new ValidationException("Invalid sort direction '{$value}'. Valid values: asc, desc");
        }

        return $valid->value;
    }

    /**
     * Validate and normalize a direction value (in/out/internal).
     */
    public static function direction(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $lower = strtolower(trim($value));
        $valid = Direction::tryFrom($lower);
        if ($valid === null) {
            $allowed = implode(', ', array_column(Direction::cases(), 'value'));
            throw new ValidationException("Invalid direction '{$value}'. Valid values: {$allowed}");
        }

        return $valid->value;
    }

    /**
     * Validate and normalize an activity type value.
     */
    public static function activityType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $upper = strtoupper(trim($value));
        $valid = ActivityType::tryFrom($upper);
        if ($valid === null) {
            $allowed = implode(', ', array_column(ActivityType::cases(), 'value'));
            throw new ValidationException("Invalid activity type '{$value}'. Valid values: {$allowed}");
        }

        return $valid->value;
    }

    /**
     * Validate and normalize an activity action value.
     */
    public static function activityAction(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $upper = strtoupper(trim($value));
        $valid = ActivityAction::tryFrom($upper);
        if ($valid === null) {
            $allowed = implode(', ', array_column(ActivityAction::cases(), 'value'));
            throw new ValidationException("Invalid activity action '{$value}'. Valid values: {$allowed}");
        }

        return $valid->value;
    }

    /**
     * Validate and clamp a 'take' pagination value.
     */
    public static function take(int $value, int $max = AbstractTools::MAX_TAKE, int $min = 1): int
    {
        return max($min, min($value, $max));
    }

    /**
     * Validate and clamp a 'skip' pagination value.
     */
    public static function skip(int $value): int
    {
        return max(0, $value);
    }

    /**
     * Validate a date string matches YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.
     */
    public static function date(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $trimmed = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed) === 1) {
            return $trimmed;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}$/', $trimmed) === 1) {
            return $trimmed;
        }

        throw new ValidationException(
            "Invalid date format '{$value}'. Expected YYYY-MM-DD or 'YYYY-MM-DD HH:MM:SS'."
        );
    }
}
