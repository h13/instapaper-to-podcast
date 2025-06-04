<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Fake;

/**
 * Fake OpenAI Client for testing TTS
 */
final class FakeOpenAiClient
{
    private bool $shouldThrowException = false;
    private ?string $exceptionMessage = null;
    private array $lastRequest = [];
    private string $audioResponse = '';

    public function setAudioResponse(string $response): void
    {
        $this->audioResponse = $response;
    }

    public function shouldThrowException(bool $should, ?string $message = null): void
    {
        $this->shouldThrowException = $should;
        $this->exceptionMessage = $message;
    }

    public function audio(): object
    {
        return new class ($this) {
            private FakeOpenAiClient $client;

            public function __construct(FakeOpenAiClient $client)
            {
                $this->client = $client;
            }

            public function speech(): object
            {
                return new class ($this->client) {
                    private FakeOpenAiClient $client;

                    public function __construct(FakeOpenAiClient $client)
                    {
                        $this->client = $client;
                    }

                    public function create(array $parameters): string
                    {
                        if ($this->client->shouldThrowException) {
                            throw new \Exception($this->client->exceptionMessage ?? 'OpenAI API error');
                        }

                        $this->client->lastRequest = $parameters;

                        // If no response set, generate a fake one
                        if (empty($this->client->audioResponse)) {
                            return $this->generateFakeAudio($parameters);
                        }

                        return $this->client->audioResponse;
                    }

                    private function generateFakeAudio(array $parameters): string
                    {
                        $text = $parameters['input'] ?? '';
                        $voice = $parameters['voice'] ?? 'nova';
                        $model = $parameters['model'] ?? 'tts-1';

                        // Create fake audio data
                        $header = "\xFF\xFB\x90\x00"; // MP3 header
                        $hash = md5($text . $voice . $model);
                        $data = $header . substr($hash, 0, 16);

                        return $data;
                    }
                };
            }
        };
    }

    public function getLastRequest(): array
    {
        return $this->lastRequest;
    }

    public function reset(): void
    {
        $this->shouldThrowException = false;
        $this->exceptionMessage = null;
        $this->lastRequest = [];
        $this->audioResponse = '';
    }
}
