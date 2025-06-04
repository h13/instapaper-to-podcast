<?php

declare(strict_types=1);

namespace InstapaperFetcher\Service;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use InstapaperFetcher\Contracts\InstapaperClientInterface;
use InstapaperFetcher\Exceptions\InstapaperApiException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

final class InstapaperClient implements InstapaperClientInterface
{
    use LoggerAwareTrait;

    private const BASE_URL = 'https://www.instapaper.com/api/';
    private Client $client;

    #[Inject]
    public function __construct(
        #[Named('instapaper.config')]
        array $config,
        ?Client $client = null,
        LoggerInterface $logger = null
    ) {
        $this->setLogger($logger ?? new \Psr\Log\NullLogger());
        
        if ($client) {
            $this->client = $client;
        } else {
            $stack = HandlerStack::create();
            $middleware = new Oauth1([
                'consumer_key' => $config['consumer_key'],
                'consumer_secret' => $config['consumer_secret'],
                'token' => $config['access_token'] ?? $config['token'] ?? '',
                'token_secret' => $config['access_token_secret'] ?? $config['token_secret'] ?? '',
            ]);
            $stack->push($middleware);

            $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'handler' => $stack,
            'auth' => 'oauth',
            'timeout' => 30,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBookmarks(int $limit = 10, string $folder = 'unread'): array
    {
        $this->logger->info('Fetching bookmarks', ['limit' => $limit, 'folder' => $folder]);

        try {
            $response = $this->client->post('1.1/bookmarks/list', [
                'form_params' => [
                    'limit' => $limit,
                    'folder_id' => $folder,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!is_array($data)) {
                throw new InstapaperApiException('Invalid response from Instapaper API');
            }

            // Filter bookmarks (exclude non-article types)
            $bookmarks = array_filter($data, function ($item) {
                return is_array($item) && 
                       isset($item['type']) && 
                       $item['type'] === 'bookmark';
            });

            $this->logger->info('Fetched bookmarks successfully', ['count' => count($bookmarks)]);

            return array_values($bookmarks);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->logger->error('Failed to fetch bookmarks', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new InstapaperApiException(
                'Failed to fetch bookmarks: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getText(int $bookmarkId): string
    {
        $this->logger->info('Fetching text for bookmark', ['bookmark_id' => $bookmarkId]);

        try {
            $response = $this->client->post('1.1/bookmarks/get_text', [
                'form_params' => [
                    'bookmark_id' => $bookmarkId,
                ],
            ]);

            $text = $response->getBody()->getContents();
            
            $this->logger->info('Fetched text successfully', [
                'bookmark_id' => $bookmarkId,
                'length' => strlen($text),
            ]);

            return $text;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->logger->error('Failed to fetch text', [
                'bookmark_id' => $bookmarkId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new InstapaperApiException(
                sprintf('Failed to fetch text for bookmark %d: %s', $bookmarkId, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function archiveBookmark(int $bookmarkId): bool
    {
        $this->logger->info('Archiving bookmark', ['bookmark_id' => $bookmarkId]);

        try {
            $response = $this->client->post('1.1/bookmarks/archive', [
                'form_params' => [
                    'bookmark_id' => $bookmarkId,
                ],
            ]);

            $success = $response->getStatusCode() === 200;
            
            if ($success) {
                $this->logger->info('Archived bookmark successfully', ['bookmark_id' => $bookmarkId]);
            } else {
                $this->logger->warning('Failed to archive bookmark', [
                    'bookmark_id' => $bookmarkId,
                    'status_code' => $response->getStatusCode(),
                ]);
            }

            return $success;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->logger->error('Failed to archive bookmark', [
                'bookmark_id' => $bookmarkId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new InstapaperApiException(
                sprintf('Failed to archive bookmark %d: %s', $bookmarkId, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}