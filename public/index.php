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

// Set JSON content type for all responses
header('Content-Type: application/json');

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
        $result = $controller->$method($data);
        
        // Return the result wrapped in a response object
        http_response_code(isset($result['status_code']) ? $result['status_code'] : 200);
        echo json_encode(['response' => $result]);
    } catch (\Exception $e) {
        // Handle errors
        $code = $e->getCode() ?: 500;
        http_response_code($code);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // No route was matched
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
