<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (getenv('ENVIRONMENT', local_only: true) !== 'ci') {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
    $dotenv->load();
}
