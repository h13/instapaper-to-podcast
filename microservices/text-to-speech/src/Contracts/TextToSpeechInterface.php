<?php

declare(strict_types=1);

namespace TextToSpeech\Contracts;

interface TextToSpeechInterface
{
    /**
     * Generate speech from text
     *
     * @param string $text The text to convert to speech
     *
     * @throws \TextToSpeech\Exceptions\TtsGenerationException
     *
     * @return string The audio content (binary data)
     */
    public function generateSpeech(string $text): string;
}
