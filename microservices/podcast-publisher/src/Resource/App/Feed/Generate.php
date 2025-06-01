<?php

declare(strict_types=1);

namespace PodcastPublisher\Resource\App\Feed;

use BEAR\Resource\ResourceObject;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use PodcastPublisher\Service\PodcastService;

/**
 * Generate podcast feed
 */
final class Generate extends ResourceObject
{
    #[Inject]
    public function __construct(
        private PodcastService $podcastService,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Generate podcast feed from audio files
     */
    public function onPost(): static
    {
        $this->logger->info('Starting podcast feed generation');
        
        try {
            $result = $this->podcastService->generateFeed();
            
            if ($result['generated']) {
                $this->code = 201; // Created
                $this->body = [
                    'success' => true,
                    'result' => $result,
                    'timestamp' => date(\DateTimeInterface::ATOM),
                    '_links' => [
                        'self' => ['href' => '/feed/generate'],
                        'feed' => ['href' => '/feed'],
                        'podcast' => ['href' => $result['url']]
                    ]
                ];
            } else {
                $this->code = 404;
                $this->body = [
                    'success' => false,
                    'error' => 'No audio files found to generate feed',
                    'result' => $result,
                    'timestamp' => date(\DateTimeInterface::ATOM),
                    '_links' => [
                        'self' => ['href' => '/feed/generate'],
                        'feed' => ['href' => '/feed']
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate podcast feed', [
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