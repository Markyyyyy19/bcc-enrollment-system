<?php

$publicDirectory = realpath(__DIR__.'/public');
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$requestedFile = realpath($publicDirectory.DIRECTORY_SEPARATOR.ltrim($requestPath, '/\\'));

if ($requestPath !== '/' && $requestedFile !== false && str_starts_with($requestedFile, $publicDirectory.DIRECTORY_SEPARATOR) && is_file($requestedFile)) {
    return false;
}

require $publicDirectory.DIRECTORY_SEPARATOR.'index.php';
