<?php

declare(strict_types=1);

namespace PodcastPublisher\Tests\Fake;

use PodcastPublisher\Contracts\PodcastFeedGeneratorInterface;

/**
 * Fake implementation of PodcastFeedGenerator for testing
 * Following the "Fake it, don't mock it" principle
 */
final class FakePodcastFeedGenerator implements PodcastFeedGeneratorInterface
{
    private bool $shouldThrowException = false;
    private ?string $exceptionMessage = null;
    private array $generatedFeeds = [];
    private int $callCount = 0;
    
    public function generateFeed(array $episodes): string
    {
        $this->callCount++;
        
        if ($this->shouldThrowException) {
            throw new \Exception($this->exceptionMessage ?? 'Feed generation failed');
        }
        
        if (empty($episodes)) {
            return $this->generateEmptyFeed();
        }
        
        // Generate deterministic feed based on episodes
        $feedContent = $this->generateFakeFeed($episodes);
        
        // Store for verification
        $this->generatedFeeds[] = [
            'episodes' => $episodes,
            'feed' => $feedContent,
            'timestamp' => date(\DateTimeInterface::ATOM)
        ];
        
        return $feedContent;
    }
    
    private function generateEmptyFeed(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
    <channel>
        <title>Test Podcast</title>
        <description>Test podcast description</description>
        <link>https://example.com</link>
        <language>en-US</language>
        <itunes:author>Test Author</itunes:author>
        <itunes:image href="https://example.com/podcast.jpg"/>
    </channel>
</rss>
XML;
    }
    
    private function generateFakeFeed(array $episodes): string
    {
        $itemsXml = '';
        foreach ($episodes as $episode) {
            $itemsXml .= sprintf(
                <<<XML
        <item>
            <title>%s</title>
            <description>%s</description>
            <link>%s</link>
            <pubDate>%s</pubDate>
            <enclosure url="%s" type="audio/mpeg" length="%d"/>
            <itunes:duration>%s</itunes:duration>
        </item>

XML,
                htmlspecialchars($episode['title']),
                htmlspecialchars($episode['description']),
                htmlspecialchars($episode['audio_url']),
                date('r', strtotime($episode['published_at'])),
                htmlspecialchars($episode['audio_url']),
                $episode['size'],
                $this->formatDuration($episode['duration'])
            );
        }
        
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
    <channel>
        <title>Test Podcast</title>
        <description>Test podcast description</description>
        <link>https://example.com</link>
        <language>en-US</language>
        <itunes:author>Test Author</itunes:author>
        <itunes:image href="https://example.com/podcast.jpg"/>
        <itunes:category text="Technology"/>
        <itunes:explicit>no</itunes:explicit>
        <itunes:type>episodic</itunes:type>
$itemsXml    </channel>
</rss>
XML;
    }
    
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        
        return sprintf('%02d:%02d', $minutes, $secs);
    }
    
    // Test helper methods
    
    public function shouldThrowException(bool $should, ?string $message = null): void
    {
        $this->shouldThrowException = $should;
        $this->exceptionMessage = $message;
    }
    
    public function getCallCount(): int
    {
        return $this->callCount;
    }
    
    public function getGeneratedFeeds(): array
    {
        return $this->generatedFeeds;
    }
    
    public function getLastGeneratedFeed(): ?array
    {
        return end($this->generatedFeeds) ?: null;
    }
    
    public function reset(): void
    {
        $this->shouldThrowException = false;
        $this->exceptionMessage = null;
        $this->callCount = 0;
        $this->generatedFeeds = [];
    }
}