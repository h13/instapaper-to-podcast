<?php

declare(strict_types=1);

namespace PodcastPublisher\Module\Provider;

use InstapaperToPodcast\Contracts\PodcastFeedGeneratorInterface;
use PodcastPublisher\Service\PodcastFeedGenerator;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class PodcastModule extends AbstractModule
{
    public function __construct(
        private array $config
    ) {}

    protected function configure(): void
    {
        // Podcast feed generator
        $this->bind(PodcastFeedGeneratorInterface::class)
            ->to(PodcastFeedGenerator::class)
            ->in(Scope::SINGLETON);
        
        // Podcast configuration
        $this->bind()->annotatedWith('podcast.title')->toInstance(
            $this->config['PODCAST_TITLE'] ?? 'My Instapaper Articles'
        );
        $this->bind()->annotatedWith('podcast.description')->toInstance(
            $this->config['PODCAST_DESCRIPTION'] ?? 'Audio versions of my saved Instapaper articles'
        );
        $this->bind()->annotatedWith('podcast.author')->toInstance(
            $this->config['PODCAST_AUTHOR'] ?? 'Instapaper to Podcast'
        );
        $this->bind()->annotatedWith('podcast.email')->toInstance(
            $this->config['PODCAST_EMAIL'] ?? 'podcast@example.com'
        );
        $this->bind()->annotatedWith('podcast.category')->toInstance(
            $this->config['PODCAST_CATEGORY'] ?? 'Technology'
        );
        $this->bind()->annotatedWith('podcast.language')->toInstance(
            $this->config['PODCAST_LANGUAGE'] ?? 'en'
        );
        $this->bind()->annotatedWith('podcast.image_url')->toInstance(
            $this->config['PODCAST_IMAGE_URL'] ?? 'https://example.com/podcast-cover.jpg'
        );
        $this->bind()->annotatedWith('podcast.website_url')->toInstance(
            $this->config['PODCAST_WEBSITE_URL'] ?? 'https://example.com'
        );
    }
}