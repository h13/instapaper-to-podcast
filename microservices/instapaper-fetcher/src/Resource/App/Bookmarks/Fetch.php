<?php

declare(strict_types=1);

namespace InstapaperFetcher\Resource\App\Bookmarks;

use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\ResourceObject;
use InstapaperFetcher\Service\BookmarkFetchService;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;

/**
 * Fetch and store bookmarks from Instapaper
 */
final class Fetch extends ResourceObject
{
    #[Inject]
    public function __construct(
        private BookmarkFetchService $fetchService,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Fetch bookmarks from Instapaper and store in Cloud Storage
     */
    public function onPost(int $limit = 10): static
    {
        $this->logger->info('Starting bookmark fetch', ['limit' => $limit]);
        
        try {
            $result = $this->fetchService->fetchAndStore($limit);
            
            $this->code = 201; // Created
            $this->body = [
                'success' => true,
                'result' => $result,
                'timestamp' => date(\DateTimeInterface::ATOM),
                '_links' => [
                    'self' => ['href' => '/bookmarks/fetch'],
                    'bookmarks' => ['href' => '/bookmarks']
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch bookmarks', [
                'error' => $e->getMessage()
            ]);
            
            $this->code = 500;
            $this->body = [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date(\DateTimeInterface::ATOM)
            ];
        }
        
        return $this;
    }
}