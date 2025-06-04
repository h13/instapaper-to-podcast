<?php

declare(strict_types=1);

namespace PodcastPublisher\Service;

use PodcastPublisher\Contracts\PodcastFeedGeneratorInterface;
use Laminas\Feed\Writer\Feed;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

final class PodcastFeedGenerator implements PodcastFeedGeneratorInterface
{
    #[Inject]
    public function __construct(
        #[Named('podcast.title')]
        private string $podcastTitle,
        #[Named('podcast.description')]
        private string $podcastDescription,
        #[Named('podcast.author')]
        private string $podcastAuthor,
        #[Named('podcast.email')]
        private string $podcastEmail,
        #[Named('podcast.category')]
        private string $podcastCategory,
        #[Named('podcast.language')]
        private string $podcastLanguage,
        #[Named('podcast.image_url')]
        private string $podcastImageUrl,
        #[Named('podcast.website_url')]
        private string $podcastWebsiteUrl,
        private LoggerInterface $logger
    ) {}

    /**
     * @param array<array{
     *     bookmark_id: string,
     *     title: string,
     *     description: string,
     *     audio_url: string,
     *     duration: int,
     *     size: int,
     *     published_at: string
     * }> $episodes
     */
    public function generateFeed(array $episodes): string
    {
        $this->logger->info('Generating podcast feed', ['episodes' => count($episodes)]);
        
        try {
            $feed = new Feed();
            
            // Set feed metadata
            $feed->setTitle($this->podcastTitle);
            $feed->setDescription($this->podcastDescription);
            $feed->setLink($this->podcastWebsiteUrl);
            $feed->setFeedLink($this->podcastWebsiteUrl . '/feed.xml', 'rss');
            $feed->setDateModified(time());
            $feed->setGenerator('Instapaper to Podcast');
            $feed->setLanguage($this->podcastLanguage);
            
            // Set iTunes podcast metadata
            $feed->setItunesAuthors([$this->podcastAuthor]);
            $feed->setItunesOwner([
                'name' => $this->podcastAuthor,
                'email' => $this->podcastEmail
            ]);
            $feed->setItunesCategories([
                $this->podcastCategory => []
            ]);
            $feed->setItunesImage($this->podcastImageUrl);
            $feed->setItunesExplicit('no');
            $feed->setItunesType('episodic');
            
            // Add episodes
            foreach ($episodes as $episodeData) {
                $entry = $feed->createEntry();
                
                $entry->setTitle($episodeData['title']);
                $entry->setDescription($episodeData['description']);
                $entry->setLink($episodeData['audio_url']);
                $entry->setDateCreated(strtotime($episodeData['published_at']));
                $entry->setDateModified(strtotime($episodeData['published_at']));
                
                // Set enclosure for audio file
                $entry->setEnclosure([
                    'uri' => $episodeData['audio_url'],
                    'type' => 'audio/mpeg',
                    'length' => $episodeData['size']
                ]);
                
                // iTunes episode metadata
                $entry->setItunesDuration($this->formatDuration($episodeData['duration']));
                $entry->setItunesExplicit('no');
                
                $feed->addEntry($entry);
            }
            
            $feedXml = $feed->export('rss');
            
            $this->logger->info('Podcast feed generated successfully', [
                'episodes' => count($episodes),
                'size' => strlen($feedXml)
            ]);
            
            return $feedXml;
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate podcast feed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Format duration for iTunes (HH:MM:SS)
     */
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
}