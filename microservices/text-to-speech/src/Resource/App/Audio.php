<?php

declare(strict_types=1);

namespace TextToSpeech\Resource\App;

use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;
use TextToSpeech\Service\TextToSpeechService;

final class Audio extends ResourceObject
{
    #[Inject]
    public function __construct(
        private TextToSpeechService $ttsService
    ) {
    }

    /**
     * Get audio files list
     */
    public function onGet(int $limit = 10): static
    {
        $audioFiles = $this->ttsService->getAudioFiles($limit);

        $this->body = [
            'audio_files' => $audioFiles,
            'count' => count($audioFiles),
            '_links' => [
                'self' => ['href' => "/audio?limit={$limit}"],
                'generate' => ['href' => '/audio/generate'],
            ],
        ];

        return $this;
    }
}
