<?php

namespace splitbrain\meh;

class FileRouter extends Router
{
    public function __construct()
    {
        parent::__construct();
        $this->alto->addMatchTypes(['f' => '[a-zA-Z0-9_\\.\\-]+\.(js|css|map|json)']);
        $this->alto->addMatchTypes(['md' => '[a-zA-Z0-9_\\.\\-]+']);
    }

    protected function registerRoutes(): void
    {
        $this->alto->map('GET', '/', [FileController::class, 'home']);

        $this->alto->map('GET', '/meh/[f:file]', [FileController::class, 'dist']);
        $this->alto->map('GET', '/meh/i18n/[f:file]', [FileController::class, 'i18n']);


        $this->alto->map('GET', '/frontend/src/components/[md:file]/readme.md', [FileController::class, 'doc']);
        $this->alto->map('GET', '/doc/[md:file]', [FileController::class, 'doc']);
    }

    protected function onPreflight(): void
    {
        // Set CORS for all origins
        header('Access-Control-Allow-Origin: *');
    }

    protected function onMatch(array $match): void
    {
        [$controller, $method] = $match['target'];
        call_user_func_array([new $controller(), $method], $match['params']);
    }

    protected function onError(\Exception $e): void
    {
        $code = 500;
        if ($e instanceof HttpException) {
            $code = $e->getCode();
        }

        header('Content-Type: text/html; charset=utf-8', true, $code);
        echo '<html lang="en"><head><title>Error ' . $code . '</title></head><body>';
        echo '<h1>Error ' . $code . '</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</body></html>';
    }
}
