<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Include routes file
require_once __DIR__ . '/../src/routes.php';

// Create AltoRouter instance
$router = new AltoRouter();

// Set the base path if your app is not in the root directory
// $router->setBasePath('/myapp');

// Register all routes
registerRoutes($router);

// Match the current request
$match = $router->match();

// Parse JSON body for non-GET requests
$data = [];
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true) ?? [];
    }
} else {
    $data = $_GET;
}

// Process the request
if ($match) {
    // Add any URL parameters to the data array
    if (isset($match['params']) && is_array($match['params'])) {
        $data = array_merge($data, $match['params']);
    }
    
    // Get the controller and method
    list($controllerClass, $method) = $match['target'];
    
    try {
        // Create controller instance and call the method
        $controller = new $controllerClass();
        echo $controller->$method($data);
    } catch (Exception $e) {
        // Handle errors
        http_response_code($e->getCode() ?: 500);
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // No route was matched
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found']);
}
