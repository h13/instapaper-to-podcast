<?php

declare(strict_types=1);

namespace TextSummarizer\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use Psr\Log\LoggerInterface;
use TextSummarizer\Module\Provider\OpenAiModule;
use TextSummarizer\Module\Provider\StorageModule;

final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new PackageModule());
        $this->install(new OpenAiModule($_ENV));
        $this->install(new StorageModule($_ENV));

        // Logger binding
        $this->bind(LoggerInterface::class)->toProvider(LoggerProvider::class);
    }
}
