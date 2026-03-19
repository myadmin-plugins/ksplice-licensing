<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file for myadmin-ksplice-licensing tests.
 */

$autoloadFile = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require_once $autoloadFile;
} else {
    // Fallback: register a simple PSR-4 autoloader for the package namespace.
    spl_autoload_register(function (string $class): void {
        $prefix = 'Detain\\MyAdminKsplice\\';
        $baseDir = dirname(__DIR__) . '/src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}
