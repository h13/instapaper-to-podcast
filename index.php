<?php

/**
 * Cloud Functions エントリーポイント
 */

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use InstapaperToPodcast\InstapaperToPodcast;
use Psr\Http\Message\ServerRequestInterface;

// Cloud Functions エントリーポイントを登録
FunctionsFramework::http('instapaperToPodcast', 'instapaperToPodcast');

/**
 * Cloud Functions エントリーポイント関数
 */
function instapaperToPodcast(ServerRequestInterface $request): string
{
    try {
        // 設定を環境変数から読み込み
        $config = [
            'instapaper' => [
                'consumer_key' => getenv('INSTAPAPER_CONSUMER_KEY') ?: 'test_key',
                'consumer_secret' => getenv('INSTAPAPER_CONSUMER_SECRET') ?: 'test_secret',
                'access_token' => getenv('INSTAPAPER_ACCESS_TOKEN') ?: 'test_token',
                'access_token_secret' => getenv('INSTAPAPER_ACCESS_TOKEN_SECRET') ?: 'test_token_secret',
            ],
            'gcp' => [
                'project_id' => getenv('GCP_PROJECT_ID') ?: 'test-project',
                'credentials_path' => getenv('GOOGLE_APPLICATION_CREDENTIALS'),
            ],
            'storage' => [
                'bucket_name' => getenv('STORAGE_BUCKET_NAME') ?: 'test-bucket',
            ],
            'podcast' => [
                'title' => getenv('PODCAST_TITLE') ?: 'My Instapaper Podcast',
                'description' => getenv('PODCAST_DESCRIPTION') ?: 'Articles from Instapaper converted to audio',
                'author' => getenv('PODCAST_AUTHOR') ?: 'Instapaper to Podcast',
                'email' => getenv('PODCAST_EMAIL') ?: 'podcast@example.com',
                'category' => getenv('PODCAST_CATEGORY') ?: 'News',
                'language' => getenv('PODCAST_LANGUAGE') ?: 'ja',
            ],
            'tts' => [
                'languageCode' => getenv('TTS_LANGUAGE_CODE') ?: 'ja-JP',
                'name' => getenv('TTS_VOICE_NAME') ?: 'ja-JP-Neural2-B',
                'speakingRate' => (float)(getenv('TTS_SPEAKING_RATE') ?: 1.0),
            ],
        ];

        // パラメータを解析
        $params = [];
        if ($request->getMethod() === 'POST') {
            $body = $request->getBody()->getContents();
            if (! empty($body)) {
                $params = json_decode($body, true) ?? [];
            }
        } else {
            parse_str($request->getUri()->getQuery(), $params);
        }

        $limit = (int)($params['limit'] ?? 5);
        $folder = $params['folder'] ?? 'unread';

        // ローカルテスト用のダミーレスポンス（実際のAPI呼び出しを避ける）
        if (getenv('APP_ENV') === 'local' || php_sapi_name() === 'cli-server') {
            return json_encode([
                'status' => 'success',
                'message' => 'Local test mode - no actual processing',
                'processed' => 0,
                'failed' => 0,
                'results' => [],
                'errors' => [],
                'feedUrl' => sprintf('https://storage.googleapis.com/%s/podcast.xml', $config['storage']['bucket_name']),
                'timestamp' => date('c'),
                'config' => [
                    'limit' => $limit,
                    'folder' => $folder,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        // 本番環境での処理
        $app = new InstapaperToPodcast($config);
        $result = $app->processBookmarks($limit, $folder);

        // レスポンスを返す
        return json_encode([
            'status' => 'success',
            'message' => sprintf('Processed %d articles, %d failed', $result['processed'], $result['failed']),
            'processed' => $result['processed'],
            'failed' => $result['failed'],
            'results' => array_map(function ($r) {
                return [
                    'title' => $r['title'],
                    'audioUrl' => $r['audioUrl'],
                    'duration' => $r['duration'],
                ];
            }, $result['results']),
            'errors' => $result['errors'],
            'feedUrl' => sprintf('https://storage.googleapis.com/%s/podcast.xml', $config['storage']['bucket_name']),
            'timestamp' => date('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } catch (\Exception $e) {
        error_log('Error in Cloud Function: ' . $e->getMessage());
        error_log($e->getTraceAsString());

        return json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'timestamp' => date('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
