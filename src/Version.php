<?php

declare(strict_types=1);

namespace Daktela\McpServer;

final class Version
{
    private static ?string $version = null;

    public static function get(): string
    {
        if (self::$version !== null) {
            return self::$version;
        }

        $composerPath = \dirname(__DIR__) . '/composer.json';
        if (is_file($composerPath)) {
            $contents = file_get_contents($composerPath);
            if ($contents !== false) {
                $data = json_decode($contents, true);
                if (\is_array($data) && isset($data['version'])) {
                    self::$version = (string) $data['version'];

                    return self::$version;
                }
            }
        }

        // Fallback — check installed packages
        $installedPath = \dirname(__DIR__) . '/vendor/composer/installed.php';
        if (is_file($installedPath)) {
            $installed = require $installedPath;
            $root = $installed['root'] ?? [];
            if (isset($root['pretty_version'])) {
                self::$version = $root['pretty_version'];

                return self::$version;
            }
        }

        self::$version = 'dev';

        return self::$version;
    }
}
