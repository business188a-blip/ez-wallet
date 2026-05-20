<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
    foreach ([
        __DIR__ . '/../controllers/' . $class . '.php',
        __DIR__ . '/../models/' . $class . '.php',
        __DIR__ . '/' . $class . '.php',
    ] as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/middleware.php';
