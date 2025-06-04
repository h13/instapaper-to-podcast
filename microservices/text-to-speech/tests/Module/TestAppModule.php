<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Module;

use BEAR\Package\AbstractAppMeta;
use BEAR\Package\PackageModule;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Psr\Log\LoggerInterface;
use Ray\Di\AbstractModule;
use TextToSpeech\Contracts\TextToSpeechInterface;
use TextToSpeech\Module\LoggerProvider;
use TextToSpeech\Service\TextToSpeechGenerator;
use TextToSpeech\Tests\Fake\FakeStorageClient;
use TextToSpeech\Tests\Fake\FakeTextToSpeechClient;

/**
 * Test application module
 */
final class TestAppModule extends AbstractModule
{
    protected function configure(): void
    {
        // Bind logger
        $this->bind(LoggerInterface::class)->to(\Psr\Log\NullLogger::class);

        // Bind test implementations
        $this->bind(TextToSpeechClient::class)->toInstance(new FakeTextToSpeechClient());
        $this->bind(StorageClient::class)->toInstance(new FakeStorageClient());
        $this->bind(TextToSpeechInterface::class)->to(TextToSpeechGenerator::class);
        $this->bind(TextToSpeechGenerator::class);

        // Bind named values
        $this->bind()->annotatedWith('storage.bucket')->toInstance($_ENV['STORAGE_BUCKET_NAME'] ?? 'test-bucket');
        $this->bind()->annotatedWith('tts.language')->toInstance($_ENV['TTS_LANGUAGE_CODE'] ?? 'en-US');
        $this->bind()->annotatedWith('tts.voice')->toInstance($_ENV['TTS_VOICE_NAME'] ?? 'en-US-Neural2-F');
        $this->bind()->annotatedWith('tts.rate')->toInstance((float) ($_ENV['TTS_SPEAKING_RATE'] ?? 1.0));
        $this->bind()->annotatedWith('tts.pitch')->toInstance((float) ($_ENV['TTS_PITCH'] ?? 0.0));
    }
}