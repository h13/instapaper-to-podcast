<?php

namespace InstapaperToPodcast;

/**
 * Podcast RSS フィード生成
 */
class PodcastFeedGenerator
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'title' => 'Instapaper Podcast',
            'description' => 'Articles from Instapaper converted to audio',
            'author' => 'Instapaper to Podcast',
            'email' => 'podcast@example.com',
            'category' => 'News',
            'language' => 'ja',
            'copyright' => 'All rights reserved',
            'image' => null,
        ], $config);
    }

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
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
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

        if ($this->config['image']) {
            $image = $channel->addChild('itunes:image', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');
            $image->addAttribute('href', $this->config['image']);
        }
    }

    private function addEpisode(\SimpleXMLElement $channel, array $episode): void
    {
        $item = $channel->addChild('item');

        $item->addChild('title', htmlspecialchars($episode['title']));
        $item->addChild('description', htmlspecialchars($episode['description']));
        $item->addChild('link', htmlspecialchars($episode['articleUrl'] ?? ''));
        $item->addChild('guid', $episode['guid'])->addAttribute('isPermaLink', 'false');
        $item->addChild('pubDate', date('r', strtotime($episode['created'])));

        $enclosure = $item->addChild('enclosure');
        $enclosure->addAttribute('url', $episode['audioUrl']);
        $enclosure->addAttribute('length', $episode['size'] ?? 0);
        $enclosure->addAttribute('type', 'audio/mpeg');

        $item->addChild('itunes:author', htmlspecialchars($this->config['author']), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $item->addChild('itunes:summary', htmlspecialchars($episode['description']), 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $item->addChild('itunes:duration', $episode['duration'] ?? '00:00:00', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $item->addChild('itunes:explicit', 'no', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
    }
}
