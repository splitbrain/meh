<?php

// Include routes file
require_once __DIR__ . '/../../backend/src/routes.php';

// Load Composer's autoloader
require_once __DIR__ . '/../../backend/vendor/autoload.php';

// Create App instance with environment variables
$app = new splitbrain\meh\App();

// Create AltoRouter instance
$router = new AltoRouter();

// Set the base path if your app is not in the root directory
$router->setBasePath('/api');

// Register all routes
registerRoutes($router);

// Match the current request
$match = $router->match();

// Set JSON content type for all responses
header('Content-Type: application/json');

// Process the request
if ($match) {
    // Parse JSON body for non-GET requests
    $data = [];
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true, JSON_THROW_ON_ERROR) ?? [];
        }
    } else {
        $data = $_GET;
    }


    // Add any URL parameters to the data array
    if (isset($match['params']) && is_array($match['params'])) {
        $data = array_merge($data, $match['params']);
    }

    // Get the controller and method
    [$controllerClass, $method, $scopes] = $match['target'];

    try {
        if($scopes) {
            // Check if the user has the required scopes
            $app->checkScopes($scopes);
        }

        // Create controller instance and call the method
        $controller = new $controllerClass($app);
        $result = $controller->$method($data);

        // Return the result wrapped in a response object
        http_response_code(200);
        echo json_encode(['response' => $result], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        // Handle errors
        $code = 500;
        if($e instanceof \splitbrain\meh\HttpException) {
            $code = $e->getCode();
        }

        http_response_code($code);
        echo json_encode([
            'error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode() ?: 1,
                'trace' => $e->getTrace() // FIXME make configurable
                // FIXME add info on previous exception if any
            ]
        ], JSON_PRETTY_PRINT);
    }
} else {
    // No route was matched
    http_response_code(404);
    echo json_encode([
        'error' => [
            'message' => 'Not found',
            'code' => 404
        ], JSON_PRETTY_PRINT
    ]);
}
