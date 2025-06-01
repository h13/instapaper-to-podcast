<?php

declare(strict_types=1);

namespace TextToSpeech\Module\Provider;

use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use InstapaperToPodcast\Contracts\TextToSpeechInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use TextToSpeech\Service\TextToSpeechGenerator;

final class TtsModule extends AbstractModule
{
    public function __construct(
        private array $config
    ) {
    }

    protected function configure(): void
    {
        // Google TTS client
        $this->bind(TextToSpeechClient::class)->toProvider(TtsClientProvider::class)->in(Scope::SINGLETON);

        // Text-to-speech generator
        $this->bind(TextToSpeechInterface::class)
            ->to(TextToSpeechGenerator::class)
            ->in(Scope::SINGLETON);

        // Configuration
        $this->bind()->annotatedWith('tts.language_code')->toInstance(
            $this->config['TTS_LANGUAGE_CODE'] ?? 'en-US'
        );
        $this->bind()->annotatedWith('tts.voice_name')->toInstance(
            $this->config['TTS_VOICE_NAME'] ?? 'en-US-Wavenet-D'
        );
        $this->bind()->annotatedWith('tts.speaking_rate')->toInstance(
            (float) ($this->config['TTS_SPEAKING_RATE'] ?? 1.0)
        );
        $this->bind()->annotatedWith('tts.pitch')->toInstance(
            (float) ($this->config['TTS_PITCH'] ?? 0.0)
        );
    }
}

final class TtsClientProvider implements \Ray\Di\ProviderInterface
{
    public function __construct(
        #[\Ray\Di\Di\Named('gcp.credentials_path')]
        private ?string $credentialsPath = null
    ) {
    }

    public function get(): TextToSpeechClient
    {
        $config = [];
        if ($this->credentialsPath) {
            $config['credentials'] = $this->credentialsPath;
        }

        return new TextToSpeechClient($config);
    }
}
