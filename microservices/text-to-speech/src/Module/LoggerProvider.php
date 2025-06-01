<?php

declare(strict_types=1);

namespace TextToSpeech\Module;

use Psr\Log\LoggerInterface;
use Ray\Di\ProviderInterface;
use TextToSpeech\Infrastructure\Logging\LoggerFactory;

final class LoggerProvider implements ProviderInterface
{
    public function get(): LoggerInterface
    {
        return LoggerFactory::create('text-to-speech');
    }
}