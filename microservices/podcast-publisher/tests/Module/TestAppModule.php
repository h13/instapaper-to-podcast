<?php

declare(strict_types=1);

namespace PodcastPublisher\Tests\Module;

use BEAR\Resource\Module\ResourceModule;
use Google\Cloud\Storage\StorageClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ray\Di\AbstractModule;
use PodcastPublisher\Contracts\PodcastFeedGeneratorInterface;
use PodcastPublisher\Service\PodcastService;
use PodcastPublisher\Tests\Fake\FakePodcastFeedGenerator;
use PodcastPublisher\Tests\Fake\FakeStorageClient;

final class TestAppModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new ResourceModule('PodcastPublisher'));
        
        // Bind test implementations
        $this->bind(LoggerInterface::class)->to(NullLogger::class);
        $this->bind(PodcastFeedGeneratorInterface::class)->to(FakePodcastFeedGenerator::class);
        $this->bind(StorageClient::class)->toInstance(new FakeStorageClient());
        $this->bind(PodcastService::class);
        $this->bind('')->annotatedWith('storage.bucket')->toInstance($_ENV['STORAGE_BUCKET_NAME'] ?? 'test-bucket');
        $this->bind('')->annotatedWith('podcast.bucket')->toInstance($_ENV['PODCAST_BUCKET_NAME'] ?? 'podcast-bucket');
        $this->bind('')->annotatedWith('podcast.title')->toInstance($_ENV['PODCAST_TITLE'] ?? 'Test Podcast');
        $this->bind('')->annotatedWith('podcast.description')->toInstance($_ENV['PODCAST_DESCRIPTION'] ?? 'Test Description');
        $this->bind('')->annotatedWith('podcast.author')->toInstance($_ENV['PODCAST_AUTHOR'] ?? 'Test Author');
        $this->bind('')->annotatedWith('podcast.email')->toInstance($_ENV['PODCAST_EMAIL'] ?? 'test@example.com');
        $this->bind('')->annotatedWith('podcast.link')->toInstance($_ENV['PODCAST_WEBSITE_URL'] ?? 'https://example.com');
        $this->bind('')->annotatedWith('podcast.language')->toInstance($_ENV['PODCAST_LANGUAGE'] ?? 'en-US');
        $this->bind('')->annotatedWith('podcast.image')->toInstance($_ENV['PODCAST_IMAGE_URL'] ?? 'https://example.com/image.jpg');
        $this->bind('')->annotatedWith('podcast.image_url')->toInstance($_ENV['PODCAST_IMAGE_URL'] ?? 'https://example.com/image.jpg');
        $this->bind('')->annotatedWith('podcast.website_url')->toInstance($_ENV['PODCAST_WEBSITE_URL'] ?? 'https://example.com');
        $this->bind('')->annotatedWith('podcast.category')->toInstance($_ENV['PODCAST_CATEGORY'] ?? 'Technology');
        $this->bind('')->annotatedWith('podcast.explicit')->toInstance('no');
        $this->bind('')->annotatedWith('podcast.max_episodes')->toInstance(50);
    }
}