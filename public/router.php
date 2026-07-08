<?php

/**
 * Routeur pour le serveur PHP intégré (Docker / Render).
 * Les fichiers statiques sont servis directement, le reste passe par index.php.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
