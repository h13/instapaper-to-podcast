<?php

declare(strict_types=1);

namespace TextToSpeech\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use BEAR\Package\Provide\Router\AuraRouterModule;
use InstapaperToPodcast\Infrastructure\Logging\LoggerFactory;
use Psr\Log\LoggerInterface;
use Ray\Di\Scope;
use TextToSpeech\Module\Provider\StorageModule;
use TextToSpeech\Module\Provider\TtsModule;

final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        // Install router
        $this->install(new AuraRouterModule());

        // Install package module
        $this->install(new PackageModule());

        // Install provider modules
        $this->install(new TtsModule($_ENV));
        $this->install(new StorageModule($_ENV));

        // Logger binding
        $this->bind(LoggerInterface::class)->toProvider(LoggerProvider::class)->in(Scope::SINGLETON);
    }
}

final class LoggerProvider implements \Ray\Di\ProviderInterface
{
    public function get(): LoggerInterface
    {
        return LoggerFactory::create('text-to-speech');
    }
}
