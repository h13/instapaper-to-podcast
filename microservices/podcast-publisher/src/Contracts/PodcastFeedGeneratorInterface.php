<?php

declare(strict_types=1);

namespace PodcastPublisher\Contracts;

interface PodcastFeedGeneratorInterface
{
    /**
     * Generate podcast RSS feed from episodes
     *
     * @param array<array{
     *     bookmark_id: string,
     *     title: string,
     *     description: string,
     *     audio_url: string,
     *     duration: int,
     *     size: int,
     *     published_at: string
     * }> $episodes
     * @return string The generated RSS feed XML
     */
    public function generateFeed(array $episodes): string;
}