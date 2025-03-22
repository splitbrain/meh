<?php

namespace splitbrain\meh;

/**
 * Base class for API controllers
 *
 * Called from the ApiRouter, methods are given a flat array of parameters and are expected to return
 * JSON-serializable data.
 */
abstract class ApiController
{
    /**
     * Constructor
     *
     * @param App $app Application container
     */
    public function __construct(protected App $app, protected ?object $tokenPayload = null)
    {
    }

    /**
     * Check if the user has the required scopes
     *
     * @param null|string|string[] $required list of or single required scope(s)
     * @return bool true if all required scopes are present
     */
    public function checkScopes(null|array|string $required): bool
    {
        if ($required === null) {
            return true;
        }

        if ($this->tokenPayload === null) {
            return false;
        }

        // Check if scopes property exists
        if (!isset($this->tokenPayload->scopes)) {
            return false;
        }

        // check if required scopes are present
        foreach ((array)$required as $scope) {
            if (!in_array($scope, $this->tokenPayload->scopes)) {
                return false;
            }
        }
        return true;
    }
}
