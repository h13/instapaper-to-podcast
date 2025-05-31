<?php

declare(strict_types=1);

namespace InstapaperToPodcast;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use InstapaperToPodcast\Contracts\InstapaperClientInterface;
use InstapaperToPodcast\Exceptions\InstapaperApiException;

/**
 * Instapaper API クライアント
 *
 * @psalm-import-type Bookmark from ConfigTypes
 */
final class InstapaperClient implements InstapaperClientInterface
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $accessToken;
    private string $accessTokenSecret;
    private HttpClient $httpClient;

    public function __construct(string $consumerKey, string $consumerSecret, string $accessToken, string $accessTokenSecret)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->accessToken = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
        $this->httpClient = new HttpClient(['timeout' => 30]);
    }

    /**
     * @return list<Bookmark>
     */
    public function getBookmarks(int $limit = 10, string $folder = 'unread'): array
    {
        $url = 'https://www.instapaper.com/api/1/bookmarks/list';

        $params = [
            'limit' => $limit,
            'folder_id' => $folder,
        ];

        $headers = $this->buildOAuthHeader('POST', $url, $params);

        try {
            $response = $this->httpClient->post($url, [
                'headers' => $headers,
                'form_params' => $params,
            ]);

            $content = $response->getBody()->getContents();
            /** @var mixed $data */
            $data = json_decode($content, true);

            if (! is_array($data)) {
                throw InstapaperApiException::invalidResponse('Expected array, got ' . gettype($data));
            }

            /** @var list<Bookmark> */
            $bookmarks = [];

            /** @var mixed $item */
            foreach ($data as $item) {
                if (is_array($item) && isset($item['type']) && $item['type'] === 'bookmark') {
                    if (isset($item['bookmark_id'], $item['title'], $item['url']) &&
                        is_int($item['bookmark_id']) &&
                        is_string($item['title']) &&
                        is_string($item['url'])) {
                        /** @var Bookmark $bookmark */
                        $bookmark = [
                            'bookmark_id' => $item['bookmark_id'],
                            'title' => $item['title'],
                            'url' => $item['url'],
                        ];
                        $bookmarks[] = $bookmark;
                    }
                }
            }

            return $bookmarks;

        } catch (RequestException $e) {
            $response = $e->getResponse();
            throw InstapaperApiException::fromRequestFailure(
                $e->getMessage(),
                $response !== null ? $response->getStatusCode() : null
            );
        }
    }

    public function getText(int $bookmarkId): string
    {
        $url = 'https://www.instapaper.com/api/1/bookmarks/get_text';

        $params = ['bookmark_id' => $bookmarkId];
        $headers = $this->buildOAuthHeader('POST', $url, $params);

        try {
            $response = $this->httpClient->post($url, [
                'headers' => $headers,
                'form_params' => $params,
            ]);

            return strip_tags($response->getBody()->getContents());

        } catch (RequestException $e) {
            $response = $e->getResponse();
            throw InstapaperApiException::fromRequestFailure(
                $e->getMessage(),
                $response !== null ? $response->getStatusCode() : null
            );
        }
    }

    /**
     * @param array<string, string|int> $params
     * @return array<string, string>
     */
    private function buildOAuthHeader(string $method, string $url, array $params = []): array
    {
        $oauth = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => strval(time()),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
        ];

        $baseString = $this->buildBaseString($method, $url, array_merge($oauth, $params));
        $signingKey = rawurlencode($this->consumerSecret) . '&' . rawurlencode($this->accessTokenSecret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $headerParts = [];
        foreach ($oauth as $key => $value) {
            $headerParts[] = $key . '="' . rawurlencode($value) . '"';
        }

        return ['Authorization' => 'OAuth ' . implode(', ', $headerParts)];
    }

    /**
     * @param array<string, string|int> $params
     */
    private function buildBaseString(string $method, string $url, array $params): string
    {
        ksort($params);
        $paramString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return $method . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);
    }
}
