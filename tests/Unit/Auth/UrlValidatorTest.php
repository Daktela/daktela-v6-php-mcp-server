<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Unit\Auth;

use Daktela\McpServer\Auth\UrlValidator;
use PHPUnit\Framework\TestCase;

final class UrlValidatorTest extends TestCase
{
    public function testValidHttpsUrl(): void
    {
        $this->expectNotToPerformAssertions();
        UrlValidator::validate('https://example.daktela.com');
    }

    public function testHttpLocalhost(): void
    {
        $this->expectNotToPerformAssertions();
        UrlValidator::validate('http://localhost');
    }

    public function testHttpNonLocalhostFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only HTTPS URLs are allowed');
        UrlValidator::validate('http://example.com');
    }

    public function testInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');
        UrlValidator::validate('not-a-url');
    }

    public function testBlockedDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Blocked domain');
        UrlValidator::validate('https://metadata.google.internal');
    }

    public function testRawIpAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IP addresses are not allowed');
        UrlValidator::validate('https://192.168.1.1');
    }

    public function testHttp127001Allowed(): void
    {
        $this->expectNotToPerformAssertions();
        UrlValidator::validate('http://127.0.0.1');
    }
}
