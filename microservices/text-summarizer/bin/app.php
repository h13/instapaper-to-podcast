#!/usr/bin/env php
<?php

declare(strict_types=1);

use TextSummarizer\Bootstrap;

require dirname(__DIR__) . '/src/Bootstrap.php';

exit(Bootstrap::getApp('cli-hal-api-app')->run());