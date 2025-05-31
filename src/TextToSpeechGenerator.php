<?php

namespace InstapaperToPodcast;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;

/**
 * 音声生成クラス
 */
class TextToSpeechGenerator
{
    private TextToSpeechClient $client;
    private array $voiceConfig;

    public function __construct(array $voiceConfig = [])
    {
        $this->client = new TextToSpeechClient();
        $this->voiceConfig = array_merge([
            'languageCode' => 'ja-JP',
            'name' => 'ja-JP-Neural2-B',
            'ssmlGender' => SsmlVoiceGender::MALE,
            'speakingRate' => 1.0,
            'pitch' => 0.0,
        ], $voiceConfig);
    }

    public function generateSpeech(string $text, string $outputPath): array
    {
        $text = $this->preprocessText($text);

        $input = new SynthesisInput();
        $input->setText($text);

        $voice = new VoiceSelectionParams();
        $voice->setLanguageCode((string) $this->voiceConfig['languageCode']);
        $voice->setName((string) $this->voiceConfig['name']);
        $voice->setSsmlGender((int) $this->voiceConfig['ssmlGender']);

        $audioConfig = new AudioConfig();
        $audioConfig->setAudioEncoding(AudioEncoding::MP3);
        $audioConfig->setSpeakingRate((float) $this->voiceConfig['speakingRate']);
        $audioConfig->setPitch((float) $this->voiceConfig['pitch']);

        try {
            $response = $this->client->synthesizeSpeech($input, $voice, $audioConfig);
            $audioContent = $response->getAudioContent();

            file_put_contents($outputPath, $audioContent);

            $fileSize = filesize($outputPath);
            $duration = $this->estimateDuration($text);

            return [
                'size' => $fileSize,
                'duration' => $duration,
            ];

        } finally {
            $this->client->close();
        }
    }

    private function preprocessText(string $text): string
    {
        $text = preg_replace('/https?:\/\/[^\s]+/', 'リンク', $text);
        $text = preg_replace('/\d{10,}/', '（長い数値）', $text);
        $text = str_replace(['(', ')'], ['、', '、'], $text);

        return $text;
    }

    private function estimateDuration(string $text): string
    {
        $charCount = mb_strlen($text);
        $minutes = ceil($charCount / 300);

        return sprintf('%02d:%02d:00', floor($minutes / 60), $minutes % 60);
    }
}
