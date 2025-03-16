<?php

require_once __DIR__ . '/../vendor/autoload.php';

use splitbrain\meh\Router;
use splitbrain\meh\API\CommentsController;

// Create router instance
$router = new Router();

// Register API routes
$router->addRoute('GET', '/api/comments', CommentsController::class, 'getComments');
$router->addRoute('POST', '/api/comments', CommentsController::class, 'createComment');
$router->addRoute('PUT', '/api/comments', CommentsController::class, 'updateCommentStatus');
$router->addRoute('DELETE', '/api/comments', CommentsController::class, 'deleteComment');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Parse JSON body for non-GET requests
$data = [];
if ($method !== 'GET') {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true) ?? [];
    }
} else {
    $data = $_GET;
}

try {
    // Dispatch the request
    $response = $router->dispatch($method, $path, $data);
    echo $response;
} catch (Exception $e) {
    // Handle errors
    http_response_code($e->getCode() ?: 500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
