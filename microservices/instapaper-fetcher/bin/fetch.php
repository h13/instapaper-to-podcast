#!/usr/bin/env php
<?php

declare(strict_types=1);

use BEAR\Resource\ResourceInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var ResourceInterface $resource */
$resource = require __DIR__ . '/app.php';

// Parse command line arguments
$options = getopt('', ['limit::', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
Instapaper Fetcher CLI

Usage:
  php bin/fetch.php [options]

Options:
  --limit=N    Number of bookmarks to fetch (default: 10)
  --help       Show this help message

HELP;
    exit(0);
}

$limit = (int) ($options['limit'] ?? 10);

try {
    // Execute fetch
    $response = $resource->post('app://self/bookmarks/fetch', [
        'limit' => $limit
    ]);
    
    // Output result
    echo json_encode($response->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    
    exit($response->code === 201 ? 0 : 1);
} catch (\Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT) . PHP_EOL;
    
    exit(1);
}