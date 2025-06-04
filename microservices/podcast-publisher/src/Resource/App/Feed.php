<?php

declare(strict_types=1);

namespace PodcastPublisher\Resource\App;

use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;
use PodcastPublisher\Service\PodcastService;

final class Feed extends ResourceObject
{
    #[Inject]
    public function __construct(
        private PodcastService $podcastService
    ) {}
    
    /**
     * Get podcast feed info
     */
    public function onGet(): static
    {
        $feedInfo = $this->podcastService->getFeedInfo();
        
        $this->body = [
            'feed' => $feedInfo,
            '_links' => [
                'self' => ['href' => '/feed'],
                'generate' => ['href' => '/feed/generate']
            ]
        ];
        
        if ($feedInfo['exists'] && isset($feedInfo['url'])) {
            $this->body['_links']['podcast'] = ['href' => $feedInfo['url']];
        }
        
        return $this;
    }
}