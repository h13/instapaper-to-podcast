<?php

declare(strict_types=1);

namespace InstapaperToPodcast\Contracts;

/**
 * Interface for text-to-speech services
 */
interface TextToSpeechInterface
{
    /**
     * Generate speech from text and save to file
     * 
     * @return array{size: int, duration: string}
     * @throws \InstapaperToPodcast\Exceptions\TextProcessingException
     */
    public function generateSpeech(string $text, string $outputPath): array;
}