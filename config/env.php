<?php
declare(strict_types=1);

function load_env(string $path): void {
    if (!is_file($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $lower = strtolower($value);
        $value = match ($lower) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed {
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null) ? $default : $v;
}

function env_bool(string $key, bool $default = false): bool {
    $v = env($key);
    if ($v === null) return $default;
    return filter_var($v, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
}

function env_int(string $key, int $default = 0): int {
    $v = env($key);
    return ($v === null || $v === '') ? $default : (int)$v;
}

$__envCandidates = [
    dirname(__DIR__) . '/.env',
    dirname(__DIR__, 2) . '/.env',
];
foreach ($__envCandidates as $__p) {
    if (is_file($__p)) {
        load_env($__p);
        break;
    }
}
unset($__envCandidates, $__p);