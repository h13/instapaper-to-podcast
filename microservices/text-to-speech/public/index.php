<?php

declare(strict_types=1);

use TextToSpeech\Bootstrap;

require dirname(__DIR__) . '/src/Bootstrap.php';

$app = Bootstrap::getApp('hal-api-app');
$app->run();