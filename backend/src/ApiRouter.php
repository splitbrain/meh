<?php

namespace splitbrain\meh;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
        if(!is_a($controllerClass, ApiController::class, true)) {
            throw new HttpException('Invalid controller', 500);
        }

        // Validate the token if any
        try {
            $tokenPayload = $this->getTokenPayload($this->app->conf('jwt_secret'));
        } catch (HttpException $e) {
            if ($scopes) {
                throw $e;
            } else {
                $tokenPayload = null;
            }
        }

        // Create the controller instance
        $controller = new $controllerClass($app, $tokenPayload);

        // Check if the user has the required scopes
        if (!$controller->checkScopes($scopes)) {
            throw new HttpException('Insufficient permissions', 403);
        }

        // Call the method
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

    /**
     * Get the token payload from the Authorization header
     *
     * @return object The token payload
     * @throws HttpException If the token is invalid
     */
    public function getTokenPayload($secret): object
    {
        // get bearer token
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer (.+)$/', (string)$token, $matches)) {
            throw new HttpException('No valid token given', 401);
        }

        $token = $matches[1];

        // decode token
        try {
            return JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Exception $e) {
            throw new HttpException('Invalid token', 401, $e);
        }
    }
}
