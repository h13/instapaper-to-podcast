<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Set test environment variables directly
$_ENV['APP_ENV'] = 'test';
$_ENV['APP_DEBUG'] = true;
$_ENV['OPENAI_API_KEY'] = 'sk-test000000000000000000000000000000000000000000000';
$_ENV['OPENAI_MODEL'] = 'gpt-3.5-turbo';
$_ENV['OPENAI_MAX_TOKENS'] = 500;
$_ENV['STORAGE_BUCKET_NAME'] = 'test-bucket';
$_ENV['GCP_PROJECT_ID'] = 'test-project';
$_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = '/dev/null';
