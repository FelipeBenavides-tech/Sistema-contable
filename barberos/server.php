<?php

/*
 * Enrutador para el servidor integrado de PHP.
 *
 * Úsalo cuando "php artisan serve" no arranque:
 *     php -S 127.0.0.1:8000 -t public server.php
 *
 * Sin este archivo, "php -S" responde 404 a /livewire/livewire.js y los botones
 * de la aplicación dejan de funcionar.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// Archivos reales de /public (CSS, imágenes, build de Vite) se sirven directo.
if ($uri !== '/' && is_file(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
