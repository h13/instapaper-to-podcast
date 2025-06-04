<?php

declare(strict_types=1);

namespace TextToSpeech\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use TextToSpeech\Contracts\TextToSpeechInterface;
use TextToSpeech\Exceptions\TtsGenerationException;
use TextToSpeech\Service\TextToSpeechGenerator;
use TextToSpeech\Tests\Fake\FakeTextToSpeechClient;

/**
 * @covers \TextToSpeech\Service\TextToSpeechGenerator
 */
final class TextToSpeechGeneratorTest extends TestCase
{
    private FakeTextToSpeechClient $ttsClient;
    private TextToSpeechGenerator $generator;

    protected function setUp(): void
    {
        $this->ttsClient = new FakeTextToSpeechClient();

        $this->generator = new TextToSpeechGenerator(
            $this->ttsClient,
            'en-US',
            'en-US-Neural2-F',
            1.0,
            0.0,
            new NullLogger()
        );
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(TextToSpeechInterface::class, $this->generator);
    }

    public function testGenerateSpeechSuccess(): void
    {
        $text = 'This is a test message for text to speech conversion.';

        $audioContent = $this->generator->generateSpeech($text);

        $this->assertNotEmpty($audioContent);

        // Verify the request was made with correct parameters
        $lastRequest = $this->ttsClient->getLastRequest();
        $this->assertEquals($text, $lastRequest['text']);
        $this->assertEquals('en-US', $lastRequest['language_code']);
        $this->assertEquals('en-US-Neural2-F', $lastRequest['voice_name']);
        $this->assertEquals(1.0, $lastRequest['speaking_rate']);
        $this->assertEquals(0.0, $lastRequest['pitch']);
    }

    public function testGenerateSpeechEmptyText(): void
    {
        $this->expectException(TtsGenerationException::class);
        $this->expectExceptionMessage('Text cannot be empty');

        $this->generator->generateSpeech('');
    }

    public function testGenerateSpeechWhitespaceText(): void
    {
        $this->expectException(TtsGenerationException::class);
        $this->expectExceptionMessage('Text cannot be empty');

        $this->generator->generateSpeech('   ');
    }

    public function testGenerateSpeechApiError(): void
    {
        $this->ttsClient->shouldThrowException(true, 'API quota exceeded');

        $this->expectException(TtsGenerationException::class);
        $this->expectExceptionMessage('Failed to generate audio: API quota exceeded');

        $this->generator->generateSpeech('Test text');
    }

    public function testGenerateSpeechCustomAudioResponse(): void
    {
        $customAudio = 'CUSTOM_AUDIO_DATA';
        $this->ttsClient->setAudioResponse($customAudio);

        $audioContent = $this->generator->generateSpeech('Test text');

        $this->assertEquals($customAudio, $audioContent);
    }

    public function testGenerateSpeechMultipleCalls(): void
    {
        $texts = [
            'First paragraph.',
            'Second paragraph with more content.',
            'Third and final paragraph.',
        ];

        foreach ($texts as $text) {
            $audioContent = $this->generator->generateSpeech($text);
            $this->assertNotEmpty($audioContent);
        }

        $this->assertEquals(3, $this->ttsClient->getCallCount());
    }

    public function testGenerateSpeechWithDifferentSettings(): void
    {
        // Create generator with different settings
        $generator = new TextToSpeechGenerator(
            $this->ttsClient,
            'ja-JP',
            'ja-JP-Neural2-B',
            0.8, // Slower speaking rate
            -2.0, // Lower pitch
            new NullLogger()
        );

        $audioContent = $generator->generateSpeech('テストメッセージ');

        $this->assertNotEmpty($audioContent);

        // Verify settings were used
        $lastRequest = $this->ttsClient->getLastRequest();
        $this->assertEquals('ja-JP', $lastRequest['language_code']);
        $this->assertEquals('ja-JP-Neural2-B', $lastRequest['voice_name']);
        $this->assertEquals(0.8, $lastRequest['speaking_rate']);
        $this->assertEquals(-2.0, $lastRequest['pitch']);
    }
}
