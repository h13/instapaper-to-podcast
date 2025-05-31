<?php

namespace InstapaperToPodcast;

use GuzzleHttp\Client as HttpClient;

/**
 * テキスト要約クラス（Vertex AI使用）
 */
class TextSummarizer
{
    private string $projectId;
    private string $location;
    private HttpClient $httpClient;

    public function __construct(string $projectId, string $location = 'us-central1')
    {
        $this->projectId = $projectId;
        $this->location = $location;
        $this->httpClient = new HttpClient(['timeout' => 60]);
    }

    public function summarize(string $text, int $maxLength = 500): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $endpoint = sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/text-bison:predict',
            $this->location,
            $this->projectId,
            $this->location
        );

        $prompt = $this->buildPrompt($text, $maxLength);

        try {
            $response = $this->httpClient->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'instances' => [['prompt' => $prompt]],
                    'parameters' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 1024,
                        'topK' => 40,
                        'topP' => 0.95,
                    ],
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result['predictions'][0]['content'] ?? $text;

        } catch (\Exception $e) {
            error_log('Failed to summarize text: ' . $e->getMessage());

            return mb_substr($text, 0, $maxLength) . '...';
        }
    }

    private function buildPrompt(string $text, int $maxLength): string
    {
        return <<<PROMPT
以下の文章を{$maxLength}文字以内で要約してください。
重要なポイントを漏らさず、Podcast向けに聞きやすい日本語で要約してください。

要約のガイドライン：
- 主要な論点を明確に
- 具体的な事例や数値があれば含める
- 音声で聞いて理解しやすい文章構成
- 冒頭に記事の概要を1-2文で説明

文章:
{$text}

要約:
PROMPT;
    }

    private function getAccessToken(): string
    {
        try {
            $response = $this->httpClient->get(
                'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token',
                ['headers' => ['Metadata-Flavor' => 'Google']]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['access_token'];
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to get access token: ' . $e->getMessage());
        }
    }
}
