<?php

namespace InstapaperToPodcast;

/**
 * メインアプリケーションクラス
 *
 * @psalm-import-type AppConfig from ConfigTypes
 * @psalm-import-type Bookmark from ConfigTypes
 * @psalm-import-type Episode from ConfigTypes
 */
class InstapaperToPodcast
{
    private InstapaperClient $instapaper;
    private TextSummarizer $summarizer;
    private TextToSpeechGenerator $tts;
    private CloudStorageUploader $storage;
    private PodcastFeedGenerator $feedGenerator;

    /**
     * @param AppConfig $config
     */
    public function __construct(array $config)
    {
        $this->instapaper = new InstapaperClient(
            $config['instapaper']['consumer_key'],
            $config['instapaper']['consumer_secret'],
            $config['instapaper']['access_token'],
            $config['instapaper']['access_token_secret']
        );

        $this->summarizer = new TextSummarizer($config['gcp']['project_id']);
        $this->tts = new TextToSpeechGenerator($config['tts'] ?? []);
        $this->storage = new CloudStorageUploader(
            $config['storage']['bucket_name'],
            $config['gcp']['credentials_path'] ?? null
        );

        $feedUrl = sprintf('https://storage.googleapis.com/%s/podcast.xml', $config['storage']['bucket_name']);
        $this->feedGenerator = new PodcastFeedGenerator(array_merge(
            $config['podcast'] ?? [],
            ['feedUrl' => $feedUrl]
        ));
    }

    /**
     * ブックマークを処理してPodcast化
     *
     * @return array{results: list<Episode>, errors: list<array{bookmark_id: int, title: string, error: string}>, processed: int, failed: int}
     */
    public function processBookmarks(int $limit = 5, string $folder = 'unread'): array
    {
        /** @var list<Episode> $results */
        $results = [];
        /** @var list<array{bookmark_id: int, title: string, error: string}> $errors */
        $errors = [];

        try {
            // 1. Instapaperから記事を取得
            echo "Fetching bookmarks from Instapaper...\n";
            $bookmarks = $this->instapaper->getBookmarks($limit, $folder);

            if ($bookmarks === []) {
                echo "No bookmarks found.\n";

                return ['results' => [], 'errors' => [], 'processed' => 0, 'failed' => 0];
            }

            echo sprintf("Found %d bookmarks to process.\n", count($bookmarks));

            // 2. 各記事を処理
            foreach ($bookmarks as $index => $bookmark) {
                $bookmarkId = $bookmark['bookmark_id'];
                echo sprintf("\n[%d/%d] Processing: %s\n", $index + 1, count($bookmarks), $bookmark['title']);

                try {
                    $result = $this->processBookmark($bookmark);
                    $results[] = $result;
                    echo "✓ Successfully processed\n";

                } catch (\Exception $e) {
                    $error = [
                        'bookmark_id' => $bookmarkId,
                        'title' => $bookmark['title'],
                        'error' => $e->getMessage(),
                    ];
                    $errors[] = $error;
                    echo "✗ Failed: " . $e->getMessage() . "\n";
                }
            }

            // 3. Podcast フィードを更新
            if ($results !== []) {
                echo "\nUpdating podcast feed...\n";
                $this->updatePodcastFeed($results);
                echo "✓ Feed updated successfully\n";
            }

        } catch (\Exception $e) {
            error_log('Critical error in processBookmarks: ' . $e->getMessage());

            throw $e;
        }

        return [
            'results' => $results,
            'errors' => $errors,
            'processed' => count($results),
            'failed' => count($errors),
        ];
    }

    /**
     * 個別のブックマークを処理
     *
     * @param Bookmark $bookmark
     * @return Episode
     */
    private function processBookmark(array $bookmark): array
    {
        $bookmarkId = $bookmark['bookmark_id'];
        $title = $bookmark['title'];
        $url = $bookmark['url'];

        // 2. 記事のテキストを取得
        echo "  - Fetching article text...\n";
        $text = $this->instapaper->getText($bookmarkId);

        if (trim($text) === '') {
            throw new \RuntimeException('Article text is empty');
        }

        // 3. テキストを要約
        echo "  - Generating summary...\n";
        $summary = $this->summarizer->summarize($text, 800); // Podcast用に少し長めの要約

        // 4. 音声を生成
        echo "  - Generating speech...\n";
        $tempFile = tempnam(sys_get_temp_dir(), 'podcast_') . '.mp3';

        try {
            $audioInfo = $this->tts->generateSpeech($summary, $tempFile);

            // 5. Cloud Storageにアップロード
            echo "  - Uploading to Cloud Storage...\n";
            $timestamp = date('Y-m-d_His');
            $safeTitle = $this->sanitizeFilename($title);
            $objectName = sprintf('podcasts/%s/%s_%s.mp3', date('Y/m'), $timestamp, $safeTitle);

            $uploadResult = $this->storage->uploadFile($tempFile, $objectName, [
                'title' => $title,
                'url' => $url,
                'bookmarkId' => (string) $bookmarkId,
                'textLength' => (string) mb_strlen($text),
                'originalTextLength' => (string) mb_strlen($text),
                'summaryLength' => (string) mb_strlen($summary),
            ]);

            /** @var Episode $episode */
            $episode = [
                'bookmarkId' => $bookmarkId,
                'title' => $title,
                'description' => $summary,
                'articleUrl' => $url,
                'audioUrl' => $uploadResult['publicUrl'],
                'size' => $uploadResult['size'],
                'duration' => $audioInfo['duration'],
                'created' => $uploadResult['created'],
                'storagePath' => $objectName,
                'guid' => 'podcast-' . $bookmarkId . '-' . $timestamp,
            ];

            return $episode;

        } finally {
            // 一時ファイルを削除
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Podcast フィードを更新
     *
     * @param list<Episode> $newEpisodes
     */
    private function updatePodcastFeed(array $newEpisodes): void
    {
        // 既存のエピソードを読み込み
        $existingData = $this->storage->readJson('podcast-episodes.json');
        /** @var list<Episode> $existingEpisodes */
        $existingEpisodes = is_array($existingData) && isset($existingData['episodes']) ? $existingData['episodes'] : [];

        // 新しいエピソードを追加（重複チェック）
        /** @var array<int, Episode> $episodeMap */
        $episodeMap = [];
        foreach ($existingEpisodes as $episode) {
            if (isset($episode['bookmarkId'])) {
                $episodeMap[$episode['bookmarkId']] = $episode;
            }
        }

        foreach ($newEpisodes as $episode) {
            $episodeMap[$episode['bookmarkId']] = $episode;
        }

        // 最新順にソートして上限を設定（最新50エピソード）
        $allEpisodes = array_values($episodeMap);
        usort($allEpisodes, function (array $a, array $b): int {
            return strtotime($b['created']) - strtotime($a['created']);
        });
        $allEpisodes = array_slice($allEpisodes, 0, 50);

        // エピソードリストを保存
        $this->storage->uploadJson('podcast-episodes.json', ['episodes' => $allEpisodes]);

        // RSS フィードを生成
        $feed = $this->feedGenerator->generateFeed($allEpisodes);

        // フィードをアップロード
        $tempFile = tempnam(sys_get_temp_dir(), 'feed_') . '.xml';

        try {
            file_put_contents($tempFile, $feed);
            $this->storage->uploadFile($tempFile, 'podcast.xml', [
                'contentType' => 'application/rss+xml',
            ]);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * ファイル名をサニタイズ
     */
    private function sanitizeFilename(string $filename): string
    {
        // 日本語を保持しつつ、ファイルシステムで問題となる文字を除去
        $filename = preg_replace('/[\/\\\:*?"<>|]/', '_', $filename);
        $filename = preg_replace('/\s+/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');

        // 長すぎる場合は切り詰める
        if (mb_strlen($filename) > 100) {
            $filename = mb_substr($filename, 0, 100);
        }

        return $filename !== '' ? $filename : 'untitled';
    }
}
