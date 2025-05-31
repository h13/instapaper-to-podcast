<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Contracts;

/**
 * Interface for podcast feed generation
 */
interface PodcastFeedGeneratorInterface
{
    /**
     * Generate RSS feed from episodes
     *
     * @param list<array<string, mixed>> $episodes
     */
    public function generateFeed(array $episodes): string;
}
