<?php

declare(strict_types=1);

namespace Daktela\McpServer\Client;

use Daktela\McpServer\Cache\ReferenceDataCache;
use Daktela\McpServer\Config\ConfigResolver;
use Psr\Log\LoggerInterface;

final class DaktelaClientFactory
{
    public function __construct(
        private readonly ConfigResolver $configResolver = new ConfigResolver(),
        private readonly ReferenceDataCache $cache = new ReferenceDataCache(),
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function create(): DaktelaClient
    {
        $config = $this->configResolver->resolve();
        $client = new DaktelaClient($config, $this->cache, $this->logger);
        $client->login();

        return $client;
    }
}
