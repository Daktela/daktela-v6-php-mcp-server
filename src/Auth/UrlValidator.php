<?php

declare(strict_types=1);

namespace Daktela\McpServer\Auth;

final class UrlValidator
{
    /** @var list<string> */
    private const BLOCKED_DOMAINS = [
        'metadata.google.internal',
        'metadata.google.com',
        '169.254.169.254',
    ];

    /**
     * Validate a URL for SSRF prevention.
     *
     * @throws \InvalidArgumentException If the URL is invalid or points to a blocked resource.
     */
    public static function validate(string $url): void
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new \InvalidArgumentException('Invalid URL format.');
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);

        // Allow HTTP only for localhost
        $isLocalhost = \in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        if ($scheme !== 'https' && !($scheme === 'http' && $isLocalhost)) {
            throw new \InvalidArgumentException('Only HTTPS URLs are allowed (HTTP permitted for localhost only).');
        }

        // Block known dangerous domains
        if (\in_array($host, self::BLOCKED_DOMAINS, true)) {
            throw new \InvalidArgumentException("Blocked domain: {$host}");
        }

        // Reject raw IP addresses (except localhost)
        if (!$isLocalhost && filter_var($host, FILTER_VALIDATE_IP) !== false) {
            throw new \InvalidArgumentException('IP addresses are not allowed. Use a hostname instead.');
        }

        // Resolve hostname and check for private IP ranges
        if (!$isLocalhost) {
            $resolved = gethostbyname($host);
            if ($resolved !== $host && self::isPrivateIp($resolved)) {
                throw new \InvalidArgumentException("Hostname '{$host}' resolves to a private IP address.");
            }
        }
    }

    private static function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        $parts = explode('.', $ip);
        if (\count($parts) !== 4) {
            return false;
        }

        $first = (int) $parts[0];
        $second = (int) $parts[1];

        // 10.0.0.0/8
        if ($first === 10) {
            return true;
        }

        // 172.16.0.0/12
        if ($first === 172 && $second >= 16 && $second <= 31) {
            return true;
        }

        // 192.168.0.0/16
        if ($first === 192 && $second === 168) {
            return true;
        }

        // 127.0.0.0/8
        if ($first === 127) {
            return true;
        }

        // 169.254.0.0/16 (link-local)
        if ($first === 169 && $second === 254) {
            return true;
        }

        return false;
    }
}
