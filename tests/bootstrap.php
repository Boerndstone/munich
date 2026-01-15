<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Load .env file if it exists (for integration tests)
// Unit tests with mocks don't need this
$envFile = dirname(__DIR__).'/.env';
if (file_exists($envFile)) {
    try {
        if (method_exists(Dotenv::class, 'bootEnv')) {
            (new Dotenv())->bootEnv($envFile);
        }
    } catch (\Exception $e) {
        // Fallback for unit tests without .env
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
    }
} else {
    // Set minimal environment variables for unit tests
    $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
    $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
}

if (!empty($_SERVER['APP_DEBUG'])) {
    umask(0000);
}
