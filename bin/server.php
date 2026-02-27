#!/usr/bin/env php
<?php

declare(strict_types=1);

// Prevent PHP errors from leaking to stdout (the JSON-RPC channel).
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/../vendor/autoload.php';

use Daktela\McpServer\DaktelaMcpServer;
use Daktela\McpServer\Log\StderrJsonLogger;

$logger = new StderrJsonLogger();
$server = new DaktelaMcpServer($logger);
$server->run();
