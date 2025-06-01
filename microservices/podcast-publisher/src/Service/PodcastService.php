<?php

declare(strict_types=1);

namespace PodcastPublisher\Service;

use Google\Cloud\Storage\StorageClient;
use PodcastPublisher\Contracts\PodcastFeedGeneratorInterface;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

final class PodcastService
{
    #[Inject]
    public function __construct(
        private PodcastFeedGeneratorInterface $feedGenerator,
        private StorageClient $storageClient,
        #[Named('storage.bucket')]
        private string $bucketName,
        #[Named('podcast.bucket')]
        private string $podcastBucketName,
        private LoggerInterface $logger
    ) {}

    /**
     * Get current podcast feed info
     */
    public function getFeedInfo(): array
    {
        try {
            $bucket = $this->storageClient->bucket($this->podcastBucketName);
            $feedObject = $bucket->object('feed.xml');
            
            if (!$feedObject->exists()) {
                return [
                    'exists' => false,
                    'episodes' => 0,
                    'last_updated' => null
                ];
            }
            
            $info = $feedObject->info();
            $content = $feedObject->downloadAsString();
            
            // Parse episode count from feed
            $episodeCount = substr_count($content, '<item>');
            
            return [
                'exists' => true,
                'episodes' => $episodeCount,
                'last_updated' => $info['updated'] ?? null,
                'size' => $info['size'] ?? 0,
                'url' => sprintf('https://storage.googleapis.com/%s/feed.xml', $this->podcastBucketName)
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get feed info', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate podcast feed from audio files
     *
     * @return array{generated: bool, episodes: int, url: string}
     */
    public function generateFeed(): array
    {
        $this->logger->info('Starting podcast feed generation');

        try {
            // Collect audio files from storage
            $episodes = $this->collectEpisodes();
            
            if (empty($episodes)) {
                $this->logger->warning('No audio files found for podcast feed');
                return [
                    'generated' => false,
                    'episodes' => 0,
                    'url' => ''
                ];
            }
            
            // Generate RSS feed
            $feedXml = $this->feedGenerator->generateFeed($episodes);
            
            // Store feed in podcast bucket
            $feedUrl = $this->storeFeed($feedXml);
            
            $this->logger->info('Podcast feed generated successfully', [
                'episodes' => count($episodes),
                'url' => $feedUrl
            ]);
            
            return [
                'generated' => true,
                'episodes' => count($episodes),
                'url' => $feedUrl
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate podcast feed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Collect episodes from audio files in storage
     *
     * @return array<array{
     *     bookmark_id: string,
     *     title: string,
     *     description: string,
     *     audio_url: string,
     *     duration: int,
     *     size: int,
     *     published_at: string
     * }>
     */
    private function collectEpisodes(): array
    {
        $bucket = $this->storageClient->bucket($this->bucketName);
        $episodes = [];
        
        $objects = $bucket->objects([
            'prefix' => 'audio/',
            'maxResults' => 100 // Limit to 100 most recent episodes
        ]);
        
        foreach ($objects as $object) {
            /** @var \Google\Cloud\Storage\StorageObject $object */
            if (!str_ends_with($object->name(), '.mp3')) {
                continue;
            }
            
            try {
                $info = $object->info();
                $metadata = $info['metadata'] ?? [];
                
                // Get corresponding summary for description
                $bookmarkId = $metadata['bookmark_id'] ?? '';
                $summary = $this->getSummaryForBookmark($bookmarkId);
                
                $episodes[] = [
                    'bookmark_id' => $bookmarkId,
                    'title' => $metadata['title'] ?? 'Untitled',
                    'description' => $summary,
                    'audio_url' => sprintf(
                        'https://storage.googleapis.com/%s/%s',
                        $this->bucketName,
                        $object->name()
                    ),
                    'duration' => (int) ($metadata['duration'] ?? 0),
                    'size' => (int) ($info['size'] ?? 0),
                    'published_at' => $metadata['created_at'] ?? date(\DateTimeInterface::ATOM)
                ];
            } catch (\Exception $e) {
                $this->logger->warning('Failed to process audio file', [
                    'file' => $object->name(),
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Sort by published date (newest first)
        usort($episodes, function ($a, $b) {
            return strtotime($b['published_at']) - strtotime($a['published_at']);
        });
        
        return $episodes;
    }

    /**
     * Get summary text for a bookmark
     */
    private function getSummaryForBookmark(string $bookmarkId): string
    {
        if (empty($bookmarkId)) {
            return 'No description available.';
        }
        
        try {
            $bucket = $this->storageClient->bucket($this->bucketName);
            
            // Search for summary file
            $objects = $bucket->objects([
                'prefix' => 'summaries/',
            ]);
            
            foreach ($objects as $object) {
                if (str_contains($object->name(), "/{$bookmarkId}.json")) {
                    $content = $object->downloadAsString();
                    $data = json_decode($content, true);
                    return $data['summary'] ?? 'No description available.';
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get summary for bookmark', [
                'bookmark_id' => $bookmarkId,
                'error' => $e->getMessage()
            ]);
        }
        
        return 'No description available.';
    }

    /**
     * Store feed XML in podcast bucket
     */
    private function storeFeed(string $feedXml): string
    {
        $bucket = $this->storageClient->bucket($this->podcastBucketName);
        
        $object = $bucket->object('feed.xml');
        $object->upload($feedXml, [
            'predefinedAcl' => 'publicRead',
            'metadata' => [
                'contentType' => 'application/rss+xml',
                'cacheControl' => 'public, max-age=3600'
            ]
        ]);
        
        return sprintf('https://storage.googleapis.com/%s/feed.xml', $this->podcastBucketName);
    }
}