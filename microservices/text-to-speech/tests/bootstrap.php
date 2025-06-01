<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Set test environment variables directly
$_ENV['APP_ENV'] = 'test';
$_ENV['APP_DEBUG'] = true;
$_ENV['OPENAI_API_KEY'] = 'sk-test000000000000000000000000000000000000000000000';
$_ENV['OPENAI_VOICE'] = 'nova';
$_ENV['OPENAI_MODEL'] = 'tts-1';
$_ENV['STORAGE_BUCKET_NAME'] = 'test-bucket';
$_ENV['GCP_PROJECT_ID'] = 'test-project';
$_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = '/dev/null';
