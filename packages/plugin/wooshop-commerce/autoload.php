<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'WooShop\\Commerce\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    if (!preg_match('/^[A-Za-z0-9_\\\\]+$/', $relative)) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
