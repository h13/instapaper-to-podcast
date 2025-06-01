<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Set test environment
$_ENV['APP_ENV'] = 'test';
$_ENV['APP_DEBUG'] = true;
$_ENV['INSTAPAPER_CONSUMER_KEY'] = 'test_consumer_key';
$_ENV['INSTAPAPER_CONSUMER_SECRET'] = 'test_consumer_secret';
$_ENV['INSTAPAPER_ACCESS_TOKEN'] = 'test_access_token';
$_ENV['INSTAPAPER_ACCESS_TOKEN_SECRET'] = 'test_access_token_secret';
$_ENV['STORAGE_BUCKET_NAME'] = 'test-bucket';
$_ENV['GCP_PROJECT_ID'] = 'test-project';
$_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = '/dev/null';