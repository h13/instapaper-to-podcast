<?php

declare(strict_types=1);

namespace InstapaperToPodcast;

use InstapaperToPodcast\Contracts\PodcastFeedGeneratorInterface;

/**
 * Podcast RSS フィード生成
 */
final class PodcastFeedGenerator implements PodcastFeedGeneratorInterface
{
    /** @var array{
     *   title: string,
     *   description: string,
     *   author: string,
     *   email: string,
     *   category: string,
     *   language: string,
     *   copyright: string,
     *   image?: string|null,
     *   feedUrl?: string
     * } */
    private array $config;

    /**
     * @param array{
     *   title?: string,
     *   description?: string,
     *   author?: string,
     *   email?: string,
     *   category?: string,
     *   language?: string,
     *   copyright?: string,
     *   image?: string|null,
     *   feedUrl?: string
     * } $config
     */
    public function __construct(array $config)
    {
        /** @var array{
         *   title: string,
         *   description: string,
         *   author: string,
         *   email: string,
         *   category: string,
         *   language: string,
         *   copyright: string,
         *   image?: string|null,
         *   feedUrl?: string
         * } $mergedConfig */
        $mergedConfig = array_merge([
            'title' => 'Instapaper Podcast',
            'description' => 'Articles from Instapaper converted to audio',
            'author' => 'Instapaper to Podcast',
            'email' => 'podcast@example.com',
            'category' => 'News',
            'language' => 'ja',
            'copyright' => 'All rights reserved',
            'image' => null,
        ], $config);
        
        $this->config = $mergedConfig;
    }

    /**
     * @param list<array<string, mixed>> $episodes
     */
    public function generateFeed(array $episodes): string
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<rss version="2.0" ' .
            'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" ' .
            'xmlns:content="http://purl.org/rss/1.0/modules/content/" ' .
            'xmlns:atom="http://www.w3.org/2005/Atom"></rss>'
        );

        $channel = $xml->addChild('channel');

        $this->addChannelInfo($channel);

        foreach ($episodes as $episode) {
            $this->addEpisode($channel, $episode);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $xmlString = $xml->asXML();
        if ($xmlString === false) {
            throw new \RuntimeException('Failed to generate XML');
        }
        $dom->loadXML($xmlString);

        $result = $dom->saveXML();
        if ($result === false) {
            throw new \RuntimeException('Failed to save XML');
        }
        return $result;
    }

    private function addChannelInfo(\SimpleXMLElement $channel): void
    {
        $channel->addChild('title', htmlspecialchars($this->config['title']));
        $channel->addChild('description', htmlspecialchars($this->config['description']));
        $channel->addChild('language', $this->config['language']);
        $channel->addChild('copyright', htmlspecialchars($this->config['copyright']));
        $channel->addChild('lastBuildDate', date('r'));
        $channel->addChild('generator', 'Instapaper to Podcast');

        if (isset($this->config['feedUrl'])) {
            $atomLink = $channel->addChild('atom:link', null, 'http://www.w3.org/2005/Atom');
            $atomLink->addAttribute('href', $this->config['feedUrl']);
            $atomLink->addAttribute('rel', 'self');
            $atomLink->addAttribute('type', 'application/rss+xml');
        }

        $channel->addChild('itunes:author', htmlspecialchars($this->config['author']), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $channel->addChild('itunes:summary', htmlspecialchars($this->config['description']), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $channel->addChild('itunes:explicit', 'no', 'http://www.itunes.com/dtds/podcast-1.0.dtd');

        $owner = $channel->addChild('itunes:owner', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $owner->addChild('itunes:name', htmlspecialchars($this->config['author']), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $owner->addChild('itunes:email', $this->config['email'], 'http://www.itunes.com/dtds/podcast-1.0.dtd');

        $category = $channel->addChild('itunes:category', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $category->addAttribute('text', $this->config['category']);

        if (isset($this->config['image']) && $this->config['image'] !== '') {
            $image = $channel->addChild('itunes:image', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
            $image->addAttribute('href', $this->config['image']);
        }
    }

    /**
     * @param array<string, mixed> $episode
     */
    private function addEpisode(\SimpleXMLElement $channel, array $episode): void
    {
        $item = $channel->addChild('item');

        $title = is_scalar($episode['title']) ? (string)$episode['title'] : '';
        $description = is_scalar($episode['description']) ? (string)$episode['description'] : '';
        $articleUrl = isset($episode['articleUrl']) && is_scalar($episode['articleUrl']) ? (string)$episode['articleUrl'] : '';
        $guid = is_scalar($episode['guid']) ? (string)$episode['guid'] : '';
        $created = is_scalar($episode['created']) ? (string)$episode['created'] : '';
        
        $item->addChild('title', htmlspecialchars($title));
        $item->addChild('description', htmlspecialchars($description));
        $item->addChild('link', htmlspecialchars($articleUrl));
        $item->addChild('guid', $guid)->addAttribute('isPermaLink', 'false');
        $timestamp = strtotime($created);
        $item->addChild('pubDate', date('r', $timestamp !== false ? $timestamp : time()));

        $enclosure = $item->addChild('enclosure');
        $audioUrl = is_scalar($episode['audioUrl']) ? (string)$episode['audioUrl'] : '';
        $size = isset($episode['size']) && is_numeric($episode['size']) ? (string)$episode['size'] : '0';
        
        $enclosure->addAttribute('url', $audioUrl);
        $enclosure->addAttribute('length', $size);
        $enclosure->addAttribute('type', 'audio/mpeg');

        $item->addChild('itunes:author', htmlspecialchars($this->config['author']), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $item->addChild('itunes:summary', htmlspecialchars($description), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $duration = isset($episode['duration']) && is_scalar($episode['duration']) ? (string)$episode['duration'] : '00:00:00';
        $item->addChild('itunes:duration', $duration, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $item->addChild('itunes:explicit', 'no', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
    }
}
