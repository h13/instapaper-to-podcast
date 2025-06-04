<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Set test environment variables directly
$_ENV['APP_ENV'] = 'test';
$_ENV['APP_DEBUG'] = true;
$_ENV['STORAGE_BUCKET_NAME'] = 'test-bucket';
$_ENV['GCP_PROJECT_ID'] = 'test-project';
$_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = '/dev/null';
$_ENV['PODCAST_TITLE'] = 'Test Podcast';
$_ENV['PODCAST_DESCRIPTION'] = 'Test podcast description';
$_ENV['PODCAST_AUTHOR'] = 'Test Author';
$_ENV['PODCAST_EMAIL'] = 'test@example.com';
$_ENV['PODCAST_CATEGORY'] = 'Technology';
$_ENV['PODCAST_LANGUAGE'] = 'en-US';
$_ENV['PODCAST_IMAGE_URL'] = 'https://example.com/podcast.jpg';
$_ENV['PODCAST_FEED_URL'] = 'https://example.com/feed.xml';