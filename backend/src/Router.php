<?php

namespace splitbrain\meh;

abstract class Router
{
    protected \AltoRouter $alto;

    public function __construct()
    {
        $this->alto = new \AltoRouter();
        $this->alto->addMatchTypes(['s' => '[a-z0-9_\-]+']);

        $this->registerRoutes();
    }

    /**
     * Register all routes
     *
     * @return void
     */
    abstract protected function registerRoutes(): void;

    /**
     * Perform any preflight checks before routing
     *
     * @return void
     * @throws \Exception If an error occurs. Will be passed to onError
     */
    abstract protected function onPreflight(): void;

    /**
     * @param array $match The route match from AltoRouter
     * @return void
     * @throws \Exception If an error occurs. Will be passed to onError
     */
    abstract protected function onMatch(array $match): void;

    /**
     * Handle any errors that occur during preflight or match handling
     *
     * @param \Exception $e The exception that occurred
     * @return void
     */
    abstract protected function onError(\Exception $e): void;

    /**
     * Route the request
     *
     * @return void
     */
    public function route(): void
    {
        try {
            $this->onPreflight();
            $match = $this->alto->match();

            if ($match) {
                $this->onMatch($match);
            } else {
                throw new HttpException('Route Not Found', 404);
            }
        } catch (\Exception $e) {
            $this->onError($e);
        }
    }

    /**
     * Check if the environment is development
     *
     * @return bool
     */
    public function isDev(): bool
    {
        return ($_ENV['MEH_ENV'] ?? '') === 'dev';
    }
}
