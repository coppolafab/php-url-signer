<?php

declare(strict_types=1);

set_error_handler(function (int $errno, string $errstr, string $errfile = null, int $errline = null): bool {
    throw new ErrorException($errstr, 0, $errno, $errfile ?? '', $errline ?? 0);
});

require __DIR__ . '/../vendor/autoload.php';
