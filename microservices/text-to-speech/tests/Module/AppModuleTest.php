<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Module;

use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Application\AppInterface;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ray\Di\Injector;
use TextToSpeech\Contracts\TextToSpeechInterface;
use TextToSpeech\Module\AppModule;
use TextToSpeech\Resource\App\Audio;
use TextToSpeech\Resource\App\Audio\Generate;
use TextToSpeech\Service\TextToSpeechGenerator;
use TextToSpeech\Service\TextToSpeechService;

/**
 * @covers \TextToSpeech\Module\AppModule
 * @covers \TextToSpeech\Module\LoggerProvider
 * @covers \TextToSpeech\Service\TextToSpeechGenerator
 * @covers \TextToSpeech\Service\TextToSpeechService
 */
final class AppModuleTest extends TestCase
{
    private Injector $injector;

    protected function setUp(): void
    {
        // Set required environment variables
        $_ENV['APP_ENV'] = 'test';
        $_ENV['APP_DEBUG'] = true;
        $_ENV['TTS_LANGUAGE_CODE'] = 'en-US';
        $_ENV['TTS_VOICE_NAME'] = 'en-US-Neural2-F';
        $_ENV['TTS_SPEAKING_RATE'] = 1.0;
        $_ENV['TTS_PITCH'] = 0.0;
        $_ENV['STORAGE_BUCKET_NAME'] = 'test-bucket';
        $_ENV['GCP_PROJECT_ID'] = 'test-project';
        $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] = '/dev/null';

        $this->injector = new Injector(new TestAppModule());
    }

    protected function tearDown(): void
    {
        // Clean up environment
        unset($_ENV['APP_ENV']);
        unset($_ENV['APP_DEBUG']);
        unset($_ENV['TTS_LANGUAGE_CODE']);
        unset($_ENV['TTS_VOICE_NAME']);
        unset($_ENV['TTS_SPEAKING_RATE']);
        unset($_ENV['TTS_PITCH']);
        unset($_ENV['STORAGE_BUCKET_NAME']);
        unset($_ENV['GCP_PROJECT_ID']);
        unset($_ENV['GOOGLE_APPLICATION_CREDENTIALS']);
    }

    public function testLoggerBinding(): void
    {
        $logger = $this->injector->getInstance(LoggerInterface::class);

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testTextToSpeechClientBinding(): void
    {
        $client = $this->injector->getInstance(TextToSpeechClient::class);

        $this->assertInstanceOf(TextToSpeechClient::class, $client);
    }

    public function testStorageClientBinding(): void
    {
        $client = $this->injector->getInstance(StorageClient::class);

        $this->assertInstanceOf(StorageClient::class, $client);
    }

    public function testTextToSpeechGeneratorBinding(): void
    {
        $generator = $this->injector->getInstance(TextToSpeechInterface::class);

        $this->assertInstanceOf(TextToSpeechInterface::class, $generator);
        $this->assertInstanceOf(TextToSpeechGenerator::class, $generator);
    }

    public function testTextToSpeechServiceBinding(): void
    {
        $service = $this->injector->getInstance(TextToSpeechService::class);

        $this->assertInstanceOf(TextToSpeechService::class, $service);
    }

    public function testResourceInterface(): void
    {
        $this->markTestSkipped('ResourceInterface requires BEAR modules');
    }

    public function testAppInterface(): void
    {
        $this->markTestSkipped('AppInterface requires BEAR modules');
    }

    public function testResourceInjection(): void
    {
        $this->markTestSkipped('Resource injection test requires full BEAR setup');
    }

    public function testNamedBindings(): void
    {
        // Test named bindings are configured
        $bucketName = $this->injector->getInstance('', 'storage.bucket');
        $this->assertEquals('test-bucket', $bucketName);

        $languageCode = $this->injector->getInstance('', 'tts.language');
        $this->assertEquals('en-US', $languageCode);

        $voiceName = $this->injector->getInstance('', 'tts.voice');
        $this->assertEquals('en-US-Neural2-F', $voiceName);

        $speakingRate = $this->injector->getInstance('', 'tts.rate');
        $this->assertEquals(1.0, $speakingRate);

        $pitch = $this->injector->getInstance('', 'tts.pitch');
        $this->assertEquals(0.0, $pitch);
    }

    public function testLoggerProviderCreatesCorrectLogger(): void
    {
        $this->markTestSkipped('LoggerProvider requires LoggerFactory implementation');
    }

    public function testModuleInstallsRequiredModules(): void
    {
        $this->markTestSkipped('Module installation test requires full BEAR setup');
    }
}
