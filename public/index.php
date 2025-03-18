<?php

// Load Composer's autoloader
require_once __DIR__ . '/../backend/vendor/autoload.php';

// Define the frontend dist directory path
$frontendDistPath = __DIR__ . '/../frontend/dist';

// Create AltoRouter instance
$router = new AltoRouter();

// Get the requested URI
$requestUri = $_SERVER['REQUEST_URI'];

// If the request is for the API, redirect to the API handler
if (strpos($requestUri, '/api/') === 0) {
    include __DIR__ . '/api/index.php';
    exit;
}

// Check if the request is for a static file in the frontend/dist directory
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$filePath = $frontendDistPath . $requestPath;

// If the path is a directory, look for index.html
if (is_dir($filePath)) {
    $filePath = rtrim($filePath, '/') . '/index.html';
}

// If the file exists, serve it directly
if (file_exists($filePath) && !is_dir($filePath)) {
    // Get the file extension to determine content type
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

    // Set the appropriate content type
    switch ($extension) {
        case 'html':
            header('Content-Type: text/html');
            break;
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'js':
            header('Content-Type: application/javascript');
            break;
        case 'json':
            header('Content-Type: application/json');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'svg':
            header('Content-Type: image/svg+xml');
            break;
        default:
            header('Content-Type: application/octet-stream');
    }

    // Serve the file
    readfile($filePath);
    exit;
}

// If we get here, the file doesn't exist in frontend/dist
// Serve the main index.html for SPA routing
if($requestUri === '/') {
    $indexPath = $frontendDistPath . '/index.html';
} else {
    $indexPath = $frontendDistPath . '/404.html';
}

$indexPath = '/index.html';
if (file_exists($indexPath)) {
    header('Content-Type: text/html');
    readfile($indexPath);
} else {
    // If even the index.html doesn't exist, show a 404 error
    header('HTTP/1.0 404 Not Found');
    echo '404 - File not found';
}
