<?php

declare(strict_types=1);

namespace TextSummarizer;

use BEAR\Package\Bootstrap as PackageBootstrap;
use Koriym\EnvJson\EnvJson;

final class Bootstrap
{
    public static function autoload(): void
    {
        $autoloadFile = dirname(__DIR__) . '/vendor/autoload.php';
        if (!file_exists($autoloadFile)) {
            throw new \RuntimeException('Run composer install first');
        }
        require $autoloadFile;

        // Load and validate environment variables
        (new EnvJson())->load(dirname(__DIR__));
    }

    public static function getApp(string $context = 'cli-hal-api-app'): \BEAR\Sunday\Extension\Application\AppInterface
    {
        self::autoload();

        return (new PackageBootstrap())->getApp(__NAMESPACE__, $context);
    }
}
