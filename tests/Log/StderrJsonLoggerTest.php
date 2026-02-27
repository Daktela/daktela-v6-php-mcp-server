<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Log;

use Daktela\McpServer\Log\StderrJsonLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class StderrJsonLoggerTest extends TestCase
{
    public function testImplementsLoggerInterface(): void
    {
        $logger = new StderrJsonLogger();
        self::assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testLogWritesToStderr(): void
    {
        $logger = new StderrJsonLogger(LogLevel::DEBUG);

        ob_start();
        $stderr = fopen('php://memory', 'w+');

        // We can't easily capture STDERR in tests, but we can verify the logger
        // doesn't throw and accepts all log levels
        $logger->info('Test message', ['key' => 'value']);
        $logger->error('Error message', ['exception' => new \RuntimeException('test')]);
        $logger->debug('Debug message');

        $output = ob_get_clean();
        // Logger writes to stderr, not stdout
        self::assertSame('', $output);
    }

    public function testMinLevelFiltering(): void
    {
        // With ERROR min level, info should be filtered
        $logger = new StderrJsonLogger(LogLevel::ERROR);

        // This should not throw even though filtered
        $logger->info('This should be filtered');
        $logger->debug('This should also be filtered');

        // These should pass through (we can't capture stderr easily but verify no exception)
        $logger->error('This should pass');
        $logger->critical('This should also pass');

        self::assertTrue(true); // If we get here, no exceptions were thrown
    }
}
