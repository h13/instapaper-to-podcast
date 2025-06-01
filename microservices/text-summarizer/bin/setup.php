#!/usr/bin/env php
<?php

declare(strict_types=1);

// Simple setup script that ensures var directories exist
$dirs = [
    __DIR__ . '/../var',
    __DIR__ . '/../var/log',
    __DIR__ . '/../var/tmp',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir\n";
    }
}

echo "Setup completed.\n";