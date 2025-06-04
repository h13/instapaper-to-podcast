<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Fake;

use TextToSpeech\Contracts\TextToSpeechInterface;
use TextToSpeech\Exceptions\TtsGenerationException;

/**
 * Fake implementation of TextToSpeech for testing
 * Following the "Fake it, don't mock it" principle
 */
final class FakeTextToSpeech implements TextToSpeechInterface
{
    private bool $shouldThrowException = false;
    private ?string $exceptionMessage = null;
    private int $callCount = 0;
    private array $generatedAudios = [];

    /**
     * @throws TtsGenerationException
     */
    public function generateSpeech(string $text, string $voice = 'nova'): string
    {
        $this->callCount++;

        if ($this->shouldThrowException) {
            throw new TtsGenerationException($this->exceptionMessage ?? 'TTS generation failed');
        }

        if (trim($text) === '') {
            throw new TtsGenerationException('Text cannot be empty');
        }

        // Simulate rate limiting
        if ($this->callCount > 10) {
            throw new TtsGenerationException('Rate limit exceeded');
        }

        // Generate deterministic fake audio data based on input
        $audioData = $this->generateFakeAudioData($text, $voice);

        // Store for verification
        $this->generatedAudios[] = [
            'text' => $text,
            'voice' => $voice,
            'audio' => $audioData,
            'timestamp' => date(\DateTimeInterface::ATOM),
        ];

        return $audioData;
    }

    /**
     * Generate fake audio data that's deterministic based on input
     */
    private function generateFakeAudioData(string $text, string $voice): string
    {
        // Create a fake MP3 header
        $header = "\xFF\xFB\x90\x00"; // MP3 sync word and basic header

        // Generate deterministic content based on text and voice
        $hash = md5($text . $voice);
        $length = strlen($text);

        // Create fake audio data
        $audioData = $header;

        // Add some "frames" based on text length
        $frameCount = min(100, $length); // Limit frames to keep data small
        for ($i = 0; $i < $frameCount; $i++) {
            $audioData .= pack('C', ord($hash[$i % 32]));
        }

        // Add metadata-like suffix
        $metadata = sprintf('FAKE_AUDIO:%s:%s:%d', $voice, substr($hash, 0, 8), $length);
        $audioData .= $metadata;

        return base64_encode($audioData);
    }

    // Test helper methods

    public function shouldThrowException(bool $should, ?string $message = null): void
    {
        $this->shouldThrowException = $should;
        $this->exceptionMessage = $message;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function getGeneratedAudios(): array
    {
        return $this->generatedAudios;
    }

    public function getLastGeneratedAudio(): ?array
    {
        return end($this->generatedAudios) ?: null;
    }

    public function reset(): void
    {
        $this->shouldThrowException = false;
        $this->exceptionMessage = null;
        $this->callCount = 0;
        $this->generatedAudios = [];
    }
}
