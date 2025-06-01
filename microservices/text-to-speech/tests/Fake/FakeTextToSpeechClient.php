<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Fake;

use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechResponse;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;

/**
 * Fake Google Cloud TTS Client for testing
 */
final class FakeTextToSpeechClient extends TextToSpeechClient
{
    private bool $shouldThrowException = false;
    private ?string $exceptionMessage = null;
    private array $lastRequest = [];
    private string $audioResponse = '';
    private int $callCount = 0;

    public function __construct(array $options = [])
    {
        // Don't call parent constructor to avoid actual GCP connection
    }

    public function synthesizeSpeech(
        $input,
        $voice,
        $audioConfig,
        array $optionalArgs = []
    ) {
        $this->callCount++;

        if ($this->shouldThrowException) {
            throw new \Exception($this->exceptionMessage ?? 'TTS API error');
        }

        // Store request for verification
        $this->lastRequest = [
            'text' => $input->getText(),
            'language_code' => $voice->getLanguageCode(),
            'voice_name' => $voice->getName(),
            'audio_encoding' => $audioConfig->getAudioEncoding(),
            'speaking_rate' => $audioConfig->getSpeakingRate(),
            'pitch' => $audioConfig->getPitch(),
        ];

        // Create response
        $response = new SynthesizeSpeechResponse();

        // If no response set, generate a fake one
        if (empty($this->audioResponse)) {
            $audioContent = $this->generateFakeAudio($input->getText());
        } else {
            $audioContent = $this->audioResponse;
        }

        // Use reflection to set the audio content
        $reflection = new \ReflectionClass($response);
        $property = $reflection->getProperty('audio_content');
        $property->setAccessible(true);
        $property->setValue($response, $audioContent);

        return $response;
    }

    public function setAudioResponse(string $response): void
    {
        $this->audioResponse = $response;
    }

    public function shouldThrowException(bool $should, ?string $message = null): void
    {
        $this->shouldThrowException = $should;
        $this->exceptionMessage = $message;
    }

    public function getLastRequest(): array
    {
        return $this->lastRequest;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function reset(): void
    {
        $this->shouldThrowException = false;
        $this->exceptionMessage = null;
        $this->lastRequest = [];
        $this->audioResponse = '';
        $this->callCount = 0;
    }

    private function generateFakeAudio(string $text): string
    {
        // Create fake MP3 data
        $header = "\xFF\xFB\x90\x00"; // MP3 header
        $hash = md5($text);
        $data = $header . substr($hash, 0, 16);

        // Add some content based on text length
        $wordCount = str_word_count($text);
        for ($i = 0; $i < min($wordCount, 50); $i++) {
            $data .= chr(rand(0, 255));
        }

        return $data;
    }
}
