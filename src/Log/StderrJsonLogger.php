<?php

declare(strict_types=1);

namespace Daktela\McpServer\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Structured JSON logger that writes to stderr.
 *
 * Stderr is used because MCP stdio transport uses stdout for protocol messages.
 */
final class StderrJsonLogger extends AbstractLogger
{
    private const LEVEL_PRIORITY = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    /** @var resource */
    private $stream;

    public function __construct(
        private readonly string $minLevel = LogLevel::INFO,
    ) {
        $stream = \defined('STDERR') ? \STDERR : fopen('php://stderr', 'w');
        if ($stream === false) {
            $stream = fopen('php://output', 'w');
        }
        /** @var resource $stream */
        $this->stream = $stream;
    }

    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $level = (string) $level;
        if (!$this->isLevelEnabled($level)) {
            return;
        }

        $entry = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => (string) $message,
        ];

        if ($context !== []) {
            $entry['context'] = $this->sanitizeContext($context);
        }

        $json = json_encode($entry, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            fwrite($this->stream, $json . "\n");
        }
    }

    private function isLevelEnabled(string $level): bool
    {
        $minPriority = self::LEVEL_PRIORITY[$this->minLevel] ?? 6;
        $currentPriority = self::LEVEL_PRIORITY[$level] ?? 7;

        return $currentPriority <= $minPriority;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $result = [];
        foreach ($context as $key => $value) {
            if ($value instanceof \Throwable) {
                $result[$key] = [
                    'class' => $value::class,
                    'message' => $value->getMessage(),
                    'code' => $value->getCode(),
                ];
            } elseif (\is_object($value)) {
                $result[$key] = $value::class;
            } elseif (\is_scalar($value) || $value === null) {
                $result[$key] = $value;
            } elseif (\is_array($value)) {
                $result[$key] = $this->sanitizeContext($value);
            }
        }

        return $result;
    }
}
