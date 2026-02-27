<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Validation;

use Daktela\McpServer\Validation\InputValidator;
use Daktela\McpServer\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InputValidatorTest extends TestCase
{
    // --- stage ---

    public function testStageNull(): void
    {
        self::assertNull(InputValidator::stage(null));
    }

    #[DataProvider('validStagesProvider')]
    public function testStageValid(string $input, string $expected): void
    {
        self::assertSame($expected, InputValidator::stage($input));
    }

    public static function validStagesProvider(): array
    {
        return [
            'uppercase' => ['OPEN', 'OPEN'],
            'lowercase' => ['open', 'OPEN'],
            'mixed' => ['Close', 'CLOSE'],
            'with spaces' => [' WAIT ', 'WAIT'],
            'archive' => ['archive', 'ARCHIVE'],
        ];
    }

    public function testStageInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Invalid ticket stage.*INVALID.*Valid values: OPEN, WAIT, CLOSE, ARCHIVE/');
        InputValidator::stage('INVALID');
    }

    // --- priority ---

    public function testPriorityNull(): void
    {
        self::assertNull(InputValidator::priority(null));
    }

    public function testPriorityValid(): void
    {
        self::assertSame('LOW', InputValidator::priority('low'));
        self::assertSame('MEDIUM', InputValidator::priority('Medium'));
        self::assertSame('HIGH', InputValidator::priority('HIGH'));
    }

    public function testPriorityInvalid(): void
    {
        $this->expectException(ValidationException::class);
        InputValidator::priority('URGENT');
    }

    // --- sortDirection ---

    public function testSortDirectionValid(): void
    {
        self::assertSame('asc', InputValidator::sortDirection('ASC'));
        self::assertSame('desc', InputValidator::sortDirection('desc'));
    }

    public function testSortDirectionInvalid(): void
    {
        $this->expectException(ValidationException::class);
        InputValidator::sortDirection('ascending');
    }

    // --- direction ---

    public function testDirectionNull(): void
    {
        self::assertNull(InputValidator::direction(null));
    }

    public function testDirectionValid(): void
    {
        self::assertSame('in', InputValidator::direction('IN'));
        self::assertSame('out', InputValidator::direction('out'));
        self::assertSame('internal', InputValidator::direction('Internal'));
    }

    public function testDirectionInvalid(): void
    {
        $this->expectException(ValidationException::class);
        InputValidator::direction('inbound');
    }

    // --- activityType ---

    public function testActivityTypeNull(): void
    {
        self::assertNull(InputValidator::activityType(null));
    }

    public function testActivityTypeValid(): void
    {
        self::assertSame('CALL', InputValidator::activityType('call'));
        self::assertSame('EMAIL', InputValidator::activityType('Email'));
        self::assertSame('FBM', InputValidator::activityType('fbm'));
    }

    public function testActivityTypeInvalid(): void
    {
        $this->expectException(ValidationException::class);
        InputValidator::activityType('PHONE');
    }

    // --- activityAction ---

    public function testActivityActionNull(): void
    {
        self::assertNull(InputValidator::activityAction(null));
    }

    public function testActivityActionValid(): void
    {
        self::assertSame('OPEN', InputValidator::activityAction('open'));
        self::assertSame('CLOSE', InputValidator::activityAction('CLOSE'));
        self::assertSame('POSTPONE', InputValidator::activityAction('postpone'));
    }

    public function testActivityActionInvalid(): void
    {
        $this->expectException(ValidationException::class);
        InputValidator::activityAction('DONE');
    }

    // --- take ---

    public function testTakeClamps(): void
    {
        self::assertSame(100, InputValidator::take(100));
        self::assertSame(250, InputValidator::take(5000));
        self::assertSame(1, InputValidator::take(-5));
        self::assertSame(1, InputValidator::take(0));
        self::assertSame(50, InputValidator::take(100, 50));
    }

    // --- skip ---

    public function testSkipClamps(): void
    {
        self::assertSame(0, InputValidator::skip(0));
        self::assertSame(10, InputValidator::skip(10));
        self::assertSame(0, InputValidator::skip(-5));
    }

    // --- date ---

    public function testDateNull(): void
    {
        self::assertNull(InputValidator::date(null));
        self::assertNull(InputValidator::date(''));
    }

    public function testDateValidFormats(): void
    {
        self::assertSame('2026-02-25', InputValidator::date('2026-02-25'));
        self::assertSame('2026-02-25 14:30:00', InputValidator::date('2026-02-25 14:30:00'));
        self::assertSame('2026-02-25T14:30:00', InputValidator::date('2026-02-25T14:30:00'));
    }

    public function testDateInvalid(): void
    {
        $this->expectException(ValidationException::class);
        InputValidator::date('25/02/2026');
    }

    public function testDateInvalidPartial(): void
    {
        $this->expectException(ValidationException::class);
        InputValidator::date('2026-02');
    }
}
