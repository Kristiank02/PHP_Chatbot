<?php
declare(strict_types=1);

final class env
{
    private static bool $loaded = false;
    private static array $values = [];

    // Returns value from .env file or environment
    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();

        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    // Loads values from .env file once
    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $path = dirname(__DIR__) . '/.env';
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            if ($key === '') {
                continue;
            }

            $value = trim($parts[1]);
            $value = trim($value, "\"'");

            self::$values[$key] = $value;
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }
        }
    }
}
