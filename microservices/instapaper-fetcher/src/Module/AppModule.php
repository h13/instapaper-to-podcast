<?php

declare(strict_types=1);

namespace InstapaperFetcher\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use BEAR\Package\Provide\Router\AuraRouterModule;
use Google\Cloud\Storage\StorageClient;
use InstapaperFetcher\Module\Provider\InstapaperModule;
use InstapaperFetcher\Module\Provider\StorageModule;
use InstapaperFetcher\Infrastructure\Logging\LoggerFactory;
use Psr\Log\LoggerInterface;
use Ray\Di\AbstractModule;

final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new AuraRouterModule());
        $this->install(new PackageModule());
        $this->install(new InstapaperModule($_ENV));
        $this->install(new StorageModule($_ENV));
        
        // Logger binding
        $this->bind(LoggerInterface::class)->toProvider(LoggerProvider::class);
    }
}

final class LoggerProvider implements \Ray\Di\ProviderInterface
{
    public function get(): LoggerInterface
    {
        return LoggerFactory::create('instapaper-fetcher');
    }
}