<?php

declare(strict_types=1);

use BEAR\Package\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

return (new Bootstrap())
    ->getApp(
        name: 'InstapaperFetcher',
        context: $argv[1] ?? 'app'
    );