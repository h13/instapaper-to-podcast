<?php

declare(strict_types=1);

use BEAR\Package\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

exit((new Bootstrap())
    ->getApp(
        name: 'InstapaperFetcher',
        context: 'prod-app'
    )
    ->run());