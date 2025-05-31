<?php

namespace InstapaperToPodcast\Tests;

use InstapaperToPodcast\PodcastFeedGenerator;
use PHPUnit\Framework\TestCase;

class PodcastFeedGeneratorTest extends TestCase
{
    private PodcastFeedGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PodcastFeedGenerator([
            'title' => 'Test Podcast',
            'description' => 'Test Description',
            'author' => 'Test Author',
            'email' => 'test@example.com',
            'language' => 'ja',
        ]);
    }

    public function testGenerateFeedCreatesValidXML(): void
    {
        $episodes = [
            [
                'bookmarkId' => '123',
                'title' => 'Test Episode',
                'description' => 'Test episode description',
                'articleUrl' => 'https://example.com/article',
                'audioUrl' => 'https://storage.example.com/test.mp3',
                'size' => 1024000,
                'duration' => '00:05:00',
                'created' => '2024-01-01T00:00:00Z',
                'guid' => 'test-123',
            ],
        ];

        $feed = $this->generator->generateFeed($episodes);

        // XMLとして正しくパースできることを確認
        $xml = simplexml_load_string($feed);
        $this->assertNotFalse($xml, 'XMLのパースに失敗しました');

        // RSS要素の確認
        $this->assertEquals('rss', $xml->getName());
        $this->assertEquals('2.0', (string)$xml['version']);

        // チャンネル情報の確認
        $this->assertEquals('Test Podcast', (string)$xml->channel->title);
        $this->assertEquals('Test Description', (string)$xml->channel->description);
        $this->assertEquals('ja', (string)$xml->channel->language);

        // エピソード情報を確認
        $this->assertCount(1, $xml->channel->item);
        $item = $xml->channel->item[0];
        $this->assertEquals('Test Episode', (string)$item->title);
        $this->assertEquals('Test episode description', (string)$item->description);
        $this->assertEquals('https://example.com/article', (string)$item->link);

        // エンクロージャーの確認
        $enclosure = $item->enclosure;
        $this->assertEquals('https://storage.example.com/test.mp3', (string)$enclosure['url']);
        $this->assertEquals('1024000', (string)$enclosure['length']);
        $this->assertEquals('audio/mpeg', (string)$enclosure['type']);

        // iTunes拡張の確認
        $namespaces = $xml->getNamespaces(true);
        $this->assertArrayHasKey('itunes', $namespaces);

        // iTunesの要素を確認
        $itunesNS = $xml->channel->children($namespaces['itunes']);
        $this->assertEquals('Test Author', (string)$itunesNS->author);
        $this->assertEquals('Test Description', (string)$itunesNS->summary);
    }

    public function testGenerateFeedWithMultipleEpisodes(): void
    {
        $episodes = [];
        for ($i = 1; $i <= 3; $i++) {
            $episodes[] = [
                'bookmarkId' => (string)$i,
                'title' => "Episode $i",
                'description' => "Description for episode $i",
                'articleUrl' => "https://example.com/article$i",
                'audioUrl' => "https://storage.example.com/episode$i.mp3",
                'size' => 1024000 * $i,
                'duration' => sprintf('00:%02d:00', $i * 5),
                'created' => "2024-01-0{$i}T00:00:00Z",
                'guid' => "episode-$i",
            ];
        }

        $feed = $this->generator->generateFeed($episodes);
        $xml = simplexml_load_string($feed);

        $this->assertCount(3, $xml->channel->item);

        // 各エピソードのタイトルを確認
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals("Episode " . ($i + 1), (string)$xml->channel->item[$i]->title);
        }
    }

    public function testGenerateFeedHandlesSpecialCharacters(): void
    {
        $episodes = [
            [
                'bookmarkId' => '456',
                'title' => 'Title with <special> & "characters"',
                'description' => 'Description with \'quotes\' and & ampersands',
                'articleUrl' => 'https://example.com/article?foo=bar&baz=qux',
                'audioUrl' => 'https://storage.example.com/test.mp3',
                'size' => 1024000,
                'duration' => '00:05:00',
                'created' => '2024-01-01T00:00:00Z',
                'guid' => 'test-456',
            ],
        ];

        $feed = $this->generator->generateFeed($episodes);
        $xml = simplexml_load_string($feed);

        // 特殊文字が正しくエスケープされていることを確認
        $this->assertNotFalse($xml);
        $item = $xml->channel->item[0];
        $this->assertEquals('Title with <special> & "characters"', (string)$item->title);
        $this->assertEquals('Description with \'quotes\' and & ampersands', (string)$item->description);
    }
}
