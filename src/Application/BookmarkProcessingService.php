<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Application;

use InstapaperToPodcast\Contracts\InstapaperClientInterface;
use InstapaperToPodcast\Contracts\StorageInterface;
use InstapaperToPodcast\Contracts\TextSummarizerInterface;
use InstapaperToPodcast\Contracts\TextToSpeechInterface;
use InstapaperToPodcast\Domain\Bookmark\Bookmark;
use InstapaperToPodcast\Domain\Bookmark\BookmarkRepository;
use InstapaperToPodcast\Infrastructure\Cache\CacheManager;
use InstapaperToPodcast\Infrastructure\Logging\LoggerAwareTrait;
use InstapaperToPodcast\Infrastructure\Resilience\CircuitBreaker;
use InstapaperToPodcast\Infrastructure\Resilience\RetryPolicy;
use InstapaperToPodcast\ValueObjects\BookmarkId;
use InstapaperToPodcast\ValueObjects\Url;

/**
 * Application service for processing bookmarks
 *
 * @psalm-suppress UnusedClass
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class BookmarkProcessingService
{
    use LoggerAwareTrait;

    private InstapaperClientInterface $instapaperClient;
    private TextSummarizerInterface $summarizer;
    private TextToSpeechInterface $textToSpeech;
    private StorageInterface $storage;
    private BookmarkRepository $repository;
    private CacheManager $cache;
    private CircuitBreaker $circuitBreaker;
    private RetryPolicy $retryPolicy;

    public function __construct(
        InstapaperClientInterface $instapaperClient,
        TextSummarizerInterface $summarizer,
        TextToSpeechInterface $textToSpeech,
        StorageInterface $storage,
        BookmarkRepository $repository,
        CacheManager $cache,
        CircuitBreaker $circuitBreaker,
        RetryPolicy $retryPolicy
    ) {
        $this->instapaperClient = $instapaperClient;
        $this->summarizer = $summarizer;
        $this->textToSpeech = $textToSpeech;
        $this->storage = $storage;
        $this->repository = $repository;
        $this->cache = $cache;
        $this->circuitBreaker = $circuitBreaker;
        $this->retryPolicy = $retryPolicy;
        $this->initializeLogger();
    }

    /**
     * Initialize logger to satisfy psalm
     */
    private function initializeLogger(): void
    {
        // Logger will be initialized by getLogger() in trait when needed
    }

    /**
     * Process bookmarks from Instapaper
     *
     * @return array{processed: int, failed: int, errors: list<array{id: int, error: string}>}
     */
    public function processBookmarks(int $limit = 10): array
    {
        $this->logInfo('Starting bookmark processing', ['limit' => $limit]);

        $processed = 0;
        $failed = 0;
        $errors = [];

        try {
            // Fetch bookmarks with circuit breaker and retry
            $bookmarks = $this->circuitBreaker->call(
                fn () => $this->retryPolicy->execute(
                    fn () => $this->instapaperClient->getBookmarks($limit)
                )
            );

            $this->logInfo('Fetched bookmarks', ['count' => count($bookmarks)]);

            foreach ($bookmarks as $bookmarkData) {
                try {
                    $this->processBookmark($bookmarkData);
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'id' => $bookmarkData['bookmark_id'],
                        'error' => $e->getMessage(),
                    ];

                    $this->logError('Failed to process bookmark', [
                        'bookmark_id' => $bookmarkData['bookmark_id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logError('Failed to fetch bookmarks', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->logInfo('Bookmark processing completed', [
            'processed' => $processed,
            'failed' => $failed,
        ]);

        return [
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * @param array{bookmark_id: int, title: string, url: string, type?: string} $bookmarkData
     */
    private function processBookmark(array $bookmarkData): void
    {
        $bookmarkId = new BookmarkId($bookmarkData['bookmark_id']);

        // Check if already processed
        if ($this->repository->exists($bookmarkId)) {
            $this->logDebug('Bookmark already processed', ['id' => $bookmarkId->getValue()]);

            return;
        }

        // Create domain entity
        $bookmark = Bookmark::create(
            $bookmarkId,
            $bookmarkData['title'],
            new Url($bookmarkData['url']),
            new \DateTimeImmutable()
        );

        // Fetch text with caching
        $text = $this->cache->remember(
            "bookmark_text_{$bookmarkId->getValue()}",
            3600,
            fn () => $this->instapaperClient->getText($bookmarkId->getValue())
        );

        $bookmark->fetchText($text);

        // Summarize text
        $summary = $this->cache->remember(
            "bookmark_summary_{$bookmarkId->getValue()}",
            86400,
            fn () => $this->summarizer->summarize($text)
        );

        $bookmark->summarize($summary);

        // Generate speech
        $audioPath = $this->generateAudioFile($bookmarkId, $summary);

        // Upload to storage
        $this->uploadAudio($bookmarkId, $audioPath);

        // Mark as processed
        $bookmark->markAsProcessed();

        // Save to repository
        $this->repository->save($bookmark);

        // Clean up
        if (file_exists($audioPath)) {
            unlink($audioPath);
        }
    }

    private function generateAudioFile(BookmarkId $bookmarkId, string $text): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'podcast_') . '.mp3';

        $this->textToSpeech->generateSpeech($text, $tempFile);

        return $tempFile;
    }

    private function uploadAudio(BookmarkId $bookmarkId, string $audioPath): void
    {
        $objectName = sprintf(
            'podcasts/%s/%s.mp3',
            date('Y/m'),
            $bookmarkId->toString()
        );

        $this->storage->uploadFile($audioPath, $objectName, [
            'bookmark_id' => $bookmarkId->toString(),
            'created_at' => date(\DateTimeInterface::ATOM),
        ]);
    }
}
