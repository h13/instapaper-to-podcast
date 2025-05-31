<?php

declare(strict_types=1);

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
                'consumer_key' => (string)(getenv('INSTAPAPER_CONSUMER_KEY') !== false ? getenv('INSTAPAPER_CONSUMER_KEY') : 'test_key'),
                'consumer_secret' => (string)(getenv('INSTAPAPER_CONSUMER_SECRET') !== false ? getenv('INSTAPAPER_CONSUMER_SECRET') : 'test_secret'),
                'access_token' => (string)(getenv('INSTAPAPER_ACCESS_TOKEN') !== false ? getenv('INSTAPAPER_ACCESS_TOKEN') : 'test_token'),
                'access_token_secret' => (string)(getenv('INSTAPAPER_ACCESS_TOKEN_SECRET') !== false ? getenv('INSTAPAPER_ACCESS_TOKEN_SECRET') : 'test_token_secret'),
            ],
            'gcp' => [
                'project_id' => (string)(getenv('GCP_PROJECT_ID') !== false ? getenv('GCP_PROJECT_ID') : 'test-project'),
                'credentials_path' => getenv('GOOGLE_APPLICATION_CREDENTIALS') !== false ? (string)getenv('GOOGLE_APPLICATION_CREDENTIALS') : null,
            ],
            'storage' => [
                'bucket_name' => (string)(getenv('STORAGE_BUCKET_NAME') !== false ? getenv('STORAGE_BUCKET_NAME') : 'test-bucket'),
            ],
            'podcast' => [
                'title' => (string)(getenv('PODCAST_TITLE') !== false ? getenv('PODCAST_TITLE') : 'My Instapaper Podcast'),
                'description' => (string)(getenv('PODCAST_DESCRIPTION') !== false ? getenv('PODCAST_DESCRIPTION') : 'Articles from Instapaper converted to audio'),
                'author' => (string)(getenv('PODCAST_AUTHOR') !== false ? getenv('PODCAST_AUTHOR') : 'Instapaper to Podcast'),
                'email' => (string)(getenv('PODCAST_EMAIL') !== false ? getenv('PODCAST_EMAIL') : 'podcast@example.com'),
                'category' => (string)(getenv('PODCAST_CATEGORY') !== false ? getenv('PODCAST_CATEGORY') : 'News'),
                'language' => (string)(getenv('PODCAST_LANGUAGE') !== false ? getenv('PODCAST_LANGUAGE') : 'ja'),
            ],
            'tts' => [
                'languageCode' => (string)(getenv('TTS_LANGUAGE_CODE') !== false ? getenv('TTS_LANGUAGE_CODE') : 'ja-JP'),
                'name' => (string)(getenv('TTS_VOICE_NAME') !== false ? getenv('TTS_VOICE_NAME') : 'ja-JP-Neural2-B'),
                'speakingRate' => (float)(getenv('TTS_SPEAKING_RATE') !== false ? getenv('TTS_SPEAKING_RATE') : 1.0),
            ],
        ];

        // パラメータを解析
        /** @var array<string, mixed> $params */
        $params = [];
        if ($request->getMethod() === 'POST') {
            $body = $request->getBody()->getContents();
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $params = $decoded;
                }
            }
        } else {
            parse_str($request->getUri()->getQuery(), $params);
        }

        $limit = isset($params['limit']) && is_numeric($params['limit']) ? (int)$params['limit'] : 5;
        $folder = isset($params['folder']) && is_string($params['folder']) ? $params['folder'] : 'unread';

        // ローカルテスト用のダミーレスポンス（実際のAPI呼び出しを避ける）
        if (getenv('APP_ENV') === 'local' || php_sapi_name() === 'cli-server') {
            $json = json_encode([
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
            return $json !== false ? $json : '{}';
        }

        // 本番環境での処理
        $app = new InstapaperToPodcast($config);
        $result = $app->processBookmarks($limit, $folder);

        // レスポンスを返す
        $json = json_encode([
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
        return $json !== false ? $json : '{}';

    } catch (\Exception $e) {
        error_log('Error in Cloud Function: ' . $e->getMessage());
        error_log($e->getTraceAsString());

        $json = json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'timestamp' => date('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $json !== false ? $json : '{}';
    }
}
