<?php

declare(strict_types=1);

// Prevent PHP errors from leaking as HTML (the MCP channel expects JSON).
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

require_once __DIR__ . '/../vendor/autoload.php';

use Daktela\McpServer\Http\HttpRequestHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

try {
    // Create PSR-7 ServerRequest from PHP globals
    $psr17Factory = new Psr17Factory();
    $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    $request = $creator->fromGlobals();

    // Handle the request
    $handler = new HttpRequestHandler();
    $response = $handler->handle($request);
} catch (\Throwable $e) {
    error_log('Fatal MCP error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

    $psr17Factory ??= new Psr17Factory();
    $body = json_encode([
        'jsonrpc' => '2.0',
        'error' => ['code' => -32603, 'message' => 'Internal error: ' . $e->getMessage()],
        'id' => null,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    $response = $psr17Factory->createResponse(500)
        ->withHeader('Content-Type', 'application/json')
        ->withBody($psr17Factory->createStream($body));
}

// Emit the PSR-7 response
http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header("{$name}: {$value}", false);
    }
}

$body = $response->getBody();
if ($body->isSeekable()) {
    $body->rewind();
}

while (!$body->eof()) {
    echo $body->read(8192);
    if (\connection_aborted()) {
        break;
    }
}
