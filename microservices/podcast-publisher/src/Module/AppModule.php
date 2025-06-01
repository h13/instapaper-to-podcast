<?php

declare(strict_types=1);

namespace PodcastPublisher\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use BEAR\Package\Provide\Router\AuraRouterModule;
use InstapaperToPodcast\Infrastructure\Logging\LoggerFactory;
use PodcastPublisher\Module\Provider\PodcastModule;
use PodcastPublisher\Module\Provider\StorageModule;
use Psr\Log\LoggerInterface;
use Ray\Di\Scope;

final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        // Install router
        $this->install(new AuraRouterModule());
        
        // Install package module
        $this->install(new PackageModule());
        
        // Install provider modules
        $this->install(new PodcastModule($_ENV));
        $this->install(new StorageModule($_ENV));
        
        // Logger binding
        $this->bind(LoggerInterface::class)->toProvider(LoggerProvider::class)->in(Scope::SINGLETON);
    }
}

final class LoggerProvider implements \Ray\Di\ProviderInterface
{
    public function get(): LoggerInterface
    {
        return LoggerFactory::create('podcast-publisher');
    }
}