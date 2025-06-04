<?php

declare(strict_types=1);

namespace TextToSpeech\Service;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Psr\Log\LoggerInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;
use TextToSpeech\Contracts\TextToSpeechInterface;
use TextToSpeech\Exceptions\TtsGenerationException;

final class TextToSpeechGenerator implements TextToSpeechInterface
{
    #[Inject]
    public function __construct(
        private TextToSpeechClient $ttsClient,
        #[Named('tts.language')]
        private string $languageCode,
        #[Named('tts.voice')]
        private string $voiceName,
        #[Named('tts.rate')]
        private float $speakingRate,
        #[Named('tts.pitch')]
        private float $pitch,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Generate speech from text
     *
     * @param string $text The text to convert to speech
     * @return string The generated audio data
     * @throws TtsGenerationException
     */
    public function generateSpeech(string $text): string
    {
        if (trim($text) === '') {
            throw new TtsGenerationException('Text cannot be empty');
        }

        $this->logger->info('Generating audio', [
            'text_length' => strlen($text),
            'format' => 'mp3',
            'voice' => $this->voiceName,
        ]);

        try {
            // Set the text input to be synthesized
            $input = new SynthesisInput();
            $input->setText($text);

            // Build the voice request
            $voice = new VoiceSelectionParams();
            $voice->setLanguageCode($this->languageCode);
            $voice->setName($this->voiceName);

            // Select the type of audio file
            $audioConfig = new AudioConfig();
            $audioConfig->setAudioEncoding(AudioEncoding::MP3);
            $audioConfig->setSpeakingRate($this->speakingRate);
            $audioConfig->setPitch($this->pitch);

            // Perform the text-to-speech request
            $response = $this->ttsClient->synthesizeSpeech($input, $voice, $audioConfig);
            $audioContent = $response->getAudioContent();

            if (empty($audioContent)) {
                throw new TtsGenerationException('No audio content generated');
            }

            $this->logger->info('Audio generated successfully', [
                'size' => strlen($audioContent),
            ]);

            return $audioContent;
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate audio', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);

            throw new TtsGenerationException(
                'Failed to generate audio: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

}