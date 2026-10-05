<?php
// Load small application settings and environment values without a PHP framework.

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('APP_ENV')) {
    define('APP_ENV', env_value('APP_ENV', 'local'));
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', filter_var(env_value('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));
}

/**
 * Read a setting from the process environment or the local .env file.
 */
function env_value(string $key, ?string $default = null): ?string
{
    static $fileValues = null;

    $processValue = getenv($key);
    if ($processValue !== false) {
        return $processValue;
    }

    if ($fileValues === null) {
        $fileValues = [];
        $envFile = APP_ROOT . DIRECTORY_SEPARATOR . '.env';

        if (is_file($envFile) && is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                    $value = substr($value, 1, -1);
                }

                if ($name !== '') {
                    $fileValues[$name] = $value;
                }
            }
        }
    }

    return $fileValues[$key] ?? $default;
}
