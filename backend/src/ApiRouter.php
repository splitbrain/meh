<?php

namespace splitbrain\meh;

use splitbrain\meh\ApiControllers\CommentApiController;
use splitbrain\meh\ApiControllers\CommentListApiController;
use splitbrain\meh\ApiControllers\TokenApiController;

class ApiRouter extends Router
{
    protected ?App $app = null;

    /** @inheritdoc */
    public function __construct()
    {
        parent::__construct();
        $this->alto->setBasePath('/api');
    }

    /** @inheritdoc */
    protected function registerRoutes(): void
    {
        //  We register  [Controller, method, scope] for each route

        $this->alto->map('GET', '/[s:site]/comments', [CommentListApiController::class, 'bypost']);

        $this->alto->map('POST', '/[s:site]/comment', [CommentApiController::class, 'create', 'user']);
        $this->alto->map('GET', '/[s:site]/comment/[i:id]', [CommentApiController::class, 'get', 'admin']);

        $this->alto->map('PATCH', '/[s:site]/comment/[i:id]', [CommentApiController::class, 'edit', 'admin']);
        $this->alto->map('DELETE', '/[s:site]/comment/[i:id]', [CommentApiController::class, 'delete', 'admin']);
        $this->alto->map('PUT', '/[s:site]/comment/[i:id]/[s:status]', [CommentApiController::class, 'status', 'admin']);

        $this->alto->map('POST', '/[s:site]/token/admin', [TokenApiController::class, 'admin']);
        $this->alto->map('POST', '/[s:site]/token/refresh', [TokenApiController::class, 'refresh']);
    }

    /** @inheritdoc */
    protected function onPreflight(): void
    {
        // Set JSON content type for all responses
        header('Content-Type: application/json');
    }

    /** @inheritdoc */
    protected function onMatch(array $match): void
    {
        if (!isset($match['params']['site'])) {
            throw new HttpException('No site specified', 400);
        }
        try {
            $app = new App($match['params']['site'], new ErrorLogLogger('info'));
        } catch (\Exception $e) {
            throw new HttpException('Invalid site specified', 404);
        }

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
        [$controllerClass, $method, $scopes] = array_pad($match['target'], 3, null);

        // Check if the user has the required scopes
        if ($scopes && !$app->checkScopes($scopes)) {
            throw new HttpException('Insufficient permissions', 403);
        }

        // Create controller instance and call the method
        $controller = new $controllerClass($app);
        $result = $controller->$method($data);

        // Return the result wrapped in a response object
        http_response_code(200);
        echo json_encode(['response' => $result], JSON_PRETTY_PRINT);
    }

    /** @inheritdoc */
    protected function onError(\Exception $e): void
    {
        // Handle errors
        $code = 500;
        if ($e instanceof HttpException) {
            $code = $e->getCode();
        }

        if ($this->app) {
            $this->app->log()->critical($e->getMessage(), ['exception' => $e]);
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
}
